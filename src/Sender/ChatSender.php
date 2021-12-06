<?php
/**
 * Created for LeadVertex
 * Date: 10/18/21 3:44 PM
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Chat\Sender;

use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Core\Chat\Components\Chat\Chat;
use Leadvertex\Plugin\Core\Chat\Components\Chat\Message\Message;
use Leadvertex\Plugin\Core\Chat\Components\Chat\Message\MessageContent;
use Leadvertex\Plugin\Core\Chat\Components\MessageStatusSender\MessageStatusSender;
use Leadvertex\Plugin\Core\Chat\SendMessageQueue\ChatSenderInterface;
use Leadvertex\Plugin\Core\Chat\SendMessageQueue\ChatSendTask;

class ChatSender implements ChatSenderInterface
{

    public function __invoke(ChatSendTask $task)
    {
        $chat = $task->getChat();
        $message = $chat->getMessage();

        $setting = Settings::find()->getData();

        $content = null;
        if ($message->getContent()) {
            $prefix = $setting->get('main.prefix', '');
            $suffix = $setting->get('main.suffix', '');

            $content = new MessageContent(
                "{$prefix}{$message->getContent()->getText()}{$suffix}",
                $message->getContent()->getFormat()
            );
        }

        $response = new Chat(
            null,
            $chat->getContact(),
            $chat->getSubject(),
            new Message(
                null,
                $content,
                $message->getAttachments(),
            )
        );

        $delay = $setting->get('main.delay', 3);
        $schedule = [];

        $schedule[time() + $delay] = MessageStatusSender::SENT;
        $schedule[time() + $delay * 2] = MessageStatusSender::DELIVERED;
        $schedule[time() + $delay * 3] = MessageStatusSender::READ;
        $schedule[time() + $delay * 4] = $response;

        if ($content && stripos($content->getText(), 'error') !== false) {
            $key = time() + $delay;
            $schedule = [$key => MessageStatusSender::ERROR];
        }

        while (!empty($schedule)) {
            foreach ($schedule as $time => $task) {
                if ($time > time()) {
                    continue;
                }

                if ($task instanceof Chat) {
                    $task->send();
                } else {
                    MessageStatusSender::send(
                        $message->getId(),
                        $task
                    );
                }
                unset($schedule[$time]);
            }
            sleep(1);
        }
    }
}