<?php
declare(strict_types=1);

namespace CommentsService\Service\Comments;

use CommentsService\Service\Comments\Exception\BadResponseException;
use CommentsService\Service\Comments\Exception\InvalidDataException;
use CommentsService\Service\Comments\Model\Comment;
use CommentsService\Util\Json;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;

class Comments
{
    private ClientInterface $httpClient;
    private string $baseUri;

    public function __construct(ClientInterface $httpClient, string $baseUri)
    {
        $this->httpClient = $httpClient;
        $this->baseUri = \trim($baseUri, '/');
    }

    /**
     * @return Comment[]
     * @throws ClientExceptionInterface
     */
    public function getComments(): array
    {
        $comments = $this->sendRequest('GET', '/comments');

        return \array_map(
            fn ($comment) => new Comment($comment['name'], $comment['text'], (int) $comment['id']),
            $comments
        );
    }

    /**
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function createComment(Comment $comment): Comment
    {
        $newComment = $this->sendRequest(
            'POST',
            '/comment',
            $this->encodeBodyContent([
                'name' => $comment->getName(),
                'text' => $comment->getText(),
            ])
        );

        return new Comment($newComment['name'], $newComment['text'], (int) $newComment['id']);
    }

    /**
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function updateComment(Comment $comment): Comment
    {
        $updatedComment = $this->sendRequest(
            'PUT',
            "/comment/{$comment->getId()}",
            $this->encodeBodyContent([
                'name' => $comment->getName(),
                'text' => $comment->getText(),
            ])
        );

        return new Comment($updatedComment['name'], $updatedComment['text'], (int) $updatedComment['id']);
    }

    /**
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    private function sendRequest(string $method, string $uri, ?string $body = null): array
    {
        $request = new Request(
            $method,
            $this->baseUri . $uri,
            [
                'Content-Type' => 'application/json',
            ],
            $body
        );
        $response = $this->httpClient->sendRequest($request);
        $content = $response->getBody()->getContents();

        if ($response->getStatusCode() !== 200) {
            throw new BadResponseException($content, $response->getStatusCode());
        }

        return $this->decodeBodyContent($content);
    }

    /**
     * @throws InvalidDataException
     */
    private function decodeBodyContent(string $content): array
    {
        if (empty($content)) {
            throw new InvalidDataException("Response data is empty.");
        }

        try {
            return Json::decode($content);
        } catch (\JsonException $e) {
            throw new InvalidDataException("Invalid response data: {$e->getMessage()}.", 0, $e);
        }
    }

    /**
     * @throws InvalidDataException
     */
    private function encodeBodyContent(array $data): string
    {
        try {
            return Json::encode($data);
        } catch (\JsonException $e) {
            throw new InvalidDataException("Invalid request data: {$e->getMessage()}.", 0, $e);
        }
    }
}
