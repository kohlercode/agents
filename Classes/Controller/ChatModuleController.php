<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Controller;

use Kohlercode\Agents\Service\ChatService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

#[AsController]
final readonly class ChatModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private ChatService $chatService,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Chat');
        $moduleTemplate->makeDocHeaderModuleMenu();
        $moduleTemplate->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'agents_chat',
            displayName: 'Agents Chat',
        );

        return $moduleTemplate
            ->assignMultiple([
                'ajaxRoutes' => [
                    'listChats' => 'agents_chat_list',
                    'createChat' => 'agents_chat_create',
                    'listMessages' => 'agents_chat_messages',
                    'sendMessage' => 'agents_chat_send',
                    'setPinned' => 'agents_chat_pin',
                    'setProvider' => 'agents_chat_provider',
                ],
                'activeProviders' => $this->chatService->listActiveProviders(),
            ])
            ->renderResponse('Chat/Index');
    }
}
