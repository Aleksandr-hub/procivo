<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Webhook;

use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final readonly class ExecuteWebhookHandler
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(ExecuteWebhookMessage $message): void
    {
        $response = $this->httpClient->request($message->method, $message->url, [
            'headers' => $message->headers,
            'body' => $message->body,
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $responseBody = $response->getContent(false);

        $instanceId = ProcessInstanceId::fromString($message->processInstanceId);
        $instance = $this->instanceRepository->findById($instanceId);

        if (null === $instance) {
            throw ProcessInstanceNotFoundException::withId($message->processInstanceId);
        }

        if (!$instance->isRunning()) {
            return;
        }

        $instance->mergeVariables($message->nodeId, '_webhook', [
            '_webhook_status_' . $message->nodeId => $statusCode,
            '_webhook_response_' . $message->nodeId => $responseBody,
        ]);

        $version = $this->versionRepository->findById($instance->versionId());
        if (null === $version) {
            throw WorkflowExecutionException::invalidTransition('Version not found');
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $tokenId = TokenId::fromString($message->tokenId);
        $nodeId = NodeId::fromString($message->nodeId);

        $instance->fireWebhook($nodeId, $tokenId);
        $this->engine->resumeToken($instance, $tokenId, $graph);

        $this->instanceRepository->save($instance);
    }
}
