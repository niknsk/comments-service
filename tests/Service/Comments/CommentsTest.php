<?php
declare(strict_types=1);

namespace CommentsService\Tests\Service\Comments;

use CommentsService\Service\Comments\Comments;
use CommentsService\Service\Comments\Exception\BadResponseException;
use CommentsService\Service\Comments\Exception\InvalidDataException;
use CommentsService\Service\Comments\Model\Comment;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Http\Message\RequestMatcher\RequestMatcher;

final class CommentsTest extends TestCase
{
    /**
     * @dataProvider getCommentsSuccessDataProvider
     * @throws ClientExceptionInterface
     */
    public function testGetCommentsSuccess(int $statusCode, string $content, array $expectedComments): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comments', '', ['GET']),
            fn (): ResponseInterface => $this->getResponseMock($statusCode, $content)
        );

        $service = new Comments($client, '');
        $comments = $service->getComments();
        $this->assertEquals($expectedComments, $comments);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testGetCommentsFailure(): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comments', '', ['GET']),
            fn (): ResponseInterface =>$this->getResponseMock(200, "{'id':1,'name':'name','text':'text'}")
        );

        $service = new Comments($client, '');
        $this->expectException(InvalidDataException::class);
        $service->getComments();
    }

    /**
     * @dataProvider createCommentSuccessDataProvider
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function testCreateCommentSuccess(int $statusCode, string $content, Comment $comment, Comment $expectedComment): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comment', '', ['POST']),
            fn (): ResponseInterface => $this->getResponseMock($statusCode, $content)
        );

        $service = new Comments($client, '');
        $newComment = $service->createComment($comment);
        $this->assertEquals($expectedComment, $newComment);
    }

    /**
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function testCreateCommentFailure(): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comment', '', ['POST']),
            fn (): ResponseInterface => $this->getResponseMock(400, 'Bad Request')
        );

        $service = new Comments($client, '');
        $this->expectException(BadResponseException::class);
        $this->expectErrorMessage('Bad Request');
        $service->createComment(new Comment('name', 'text'));
    }

    /**
     * @dataProvider updateCommentSuccessDataProvider
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function testUpdateCommentSuccess(int $statusCode, string $content, Comment $comment, Comment $expectedComment): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comment/1', '', ['PUT']),
            fn (): ResponseInterface => $this->getResponseMock(200, $content)
        );

        $service = new Comments($client, '');
        $updatedComment = $service->updateComment($comment);
        $this->assertEquals($expectedComment, $updatedComment);
    }

    /**
     * @throws BadResponseException|ClientExceptionInterface|InvalidDataException
     */
    public function testUpdateCommentFailure(): void
    {
        $client = new Client();
        $client->on(
            new RequestMatcher('/comment/1', '', ['PUT']),
            fn () => $this->getResponseMock(204, '')
        );

        $service = new Comments($client, '');
        $this->expectException(BadResponseException::class);
        $service->updateComment(new Comment('name', 'text', 1));
    }

    public function getCommentsSuccessDataProvider(): array
    {
        return [
            [
                200,
                \json_encode([]),
                [],
            ],
            [
                200,
                \json_encode([
                    [
                        'id' => 1,
                        'name' => 'name_1',
                        'text' => 'text_1',
                    ],
                    [
                        'id' => 2,
                        'name' => 'name_2',
                        'text' => 'text_2',
                    ],
                    [
                        'id' => 3,
                        'name' => 'name_3',
                        'text' => 'text_3',
                    ],
                ]),
                [
                    new Comment('name_1', 'text_1', 1),
                    new Comment('name_2', 'text_2', 2),
                    new Comment('name_3', 'text_3', 3),
                ],
            ],
        ];
    }

    public function createCommentSuccessDataProvider(): array
    {
        return [
            [
                200,
                \json_encode([
                    'id' => 1,
                    'name' => 'name',
                    'text' => 'text',
                ]),
                new Comment('name', 'text'),
                new Comment('name', 'text', 1),
            ],
        ];
    }

    public function updateCommentSuccessDataProvider(): array
    {
        return [
            [
                200,
                \json_encode([
                    'id' => 1,
                    'name' => 'name',
                    'text' => 'text',
                ]),
                new Comment('name', 'text', 1),
                new Comment('name', 'text', 1),
            ],
        ];
    }

    private function getResponseMock(int $statusCode, string $content): ResponseInterface
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('getContents')->willReturn($content);
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('getStatusCode')->willReturn($statusCode);

        return $responseMock;
    }
}
