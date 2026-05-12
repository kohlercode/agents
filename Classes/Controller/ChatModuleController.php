<?php

declare(strict_types=1);

namespace Kohlercode\Agents\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

#[AsController]
final readonly class ChatModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Agents Chat');
        $moduleTemplate->makeDocHeaderModuleMenu();

        return $moduleTemplate
            ->assignMultiple([
                'ajaxRoutes' => [
                    'listChats' => 'agents_chat_list',
                    'createChat' => 'agents_chat_create',
                    'listMessages' => 'agents_chat_messages',
                    'sendMessage' => 'agents_chat_send',
                    'setPinned' => 'agents_chat_pin',
                ],
            ])
            ->renderResponse('Chat/Index');
    }
}
