<?php
/**
 * Created for LeadVertex
 * Date: 10/18/21 3:44 PM
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Dialog\Sender;

use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Core\Dialog\Components\Dialog\Dialog;
use Leadvertex\Plugin\Core\Dialog\Components\Dialog\Message\Message;
use Leadvertex\Plugin\Core\Dialog\Components\Dialog\Message\MessageContent;
use Leadvertex\Plugin\Core\Dialog\Components\MessageStatusSender\MessageStatusSender;
use Leadvertex\Plugin\Core\Dialog\SendMessageQueue\DialogSenderInterface;
use Leadvertex\Plugin\Core\Dialog\SendMessageQueue\DialogSendTask;

class DialogSender implements DialogSenderInterface
{

    public function __invoke(DialogSendTask $task)
    {
        $dialog = $task->getDialog();
        $message = $dialog->getMessage();

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

        $response = new Dialog(
            null,
            $dialog->getContact(),
            $dialog->getSubject(),
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

                if ($task instanceof Dialog) {
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