<?php

namespace Tests\Fakes;

use Anthropic\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * PSR-18 transport for the Anthropic SDK in tests: answers with queued Messages API
 * responses and records every request, so the real SDK builds and parses the calls.
 */
class FakeClaudeTransport implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    public function client(): Client
    {
        return new Client(apiKey: 'test-key', requestOptions: ['transporter' => $this, 'maxRetries' => 0]);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        return array_shift($this->responses) ?? throw new RuntimeException('No fake Claude response queued');
    }

    public function push(array $content, string $stopReason = 'end_turn', array $extra = []): self
    {
        $this->responses[] = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_' . (count($this->responses) + 1),
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => $content,
            'stop_reason' => $stopReason,
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
        ] + $extra));
        return $this;
    }

    public function text(string $text, string $stopReason = 'end_turn'): self
    {
        return $this->push([['type' => 'text', 'text' => $text]], $stopReason);
    }

    public function toolCall(string $name, array $input, string $id = 'toolu_1'): self
    {
        return $this->push([['type' => 'tool_use', 'id' => $id, 'name' => $name, 'input' => $input]], 'tool_use');
    }

    public function error(int $status): self
    {
        $this->responses[] = new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'type' => 'error',
            'error' => ['type' => 'api_error', 'message' => 'Internal server error'],
        ]));
        return $this;
    }

    /**
     * JSON body of the n-th request (0-based).
     */
    public function body(int $index): array
    {
        return json_decode((string) $this->requests[$index]->getBody(), true);
    }

    /**
     * The tool_result blocks sent with the n-th request.
     */
    public function toolResults(int $index): array
    {
        $last = last($this->body($index)['messages']);
        return array_values(array_filter($last['content'], fn ($block) => $block['type'] === 'tool_result'));
    }
}
