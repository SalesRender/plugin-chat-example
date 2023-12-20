<?php
/**
 * Created for plugin-core-chat
 * Date: 30.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Form\Autocomplete\AutocompleteRegistry;
use SalesRender\Plugin\Components\Info\Developer;
use SalesRender\Plugin\Components\Info\Info;
use SalesRender\Plugin\Components\Info\PluginType;
use SalesRender\Plugin\Components\Settings\Settings;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Core\Actions\Upload\LocalUploadAction;
use SalesRender\Plugin\Core\Actions\Upload\UploadersContainer;
use SalesRender\Plugin\Core\Chat\SendMessageQueue\ChatSendQueueHandleCommand;
use SalesRender\Plugin\Instance\Chat\Forms\SettingsForm;
use SalesRender\Plugin\Instance\Chat\Sender\ChatSender;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

# 0. Configure environment variable in .env file, that placed into root of app

# 1. Configure DB (for SQLite *.db file and parent directory should be writable)
Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => Path::root()->down('db/database.db')
]));

# 2. Set plugin default language
Translator::config('ru_RU');

# 3. Configure info about plugin
Info::config(
    new PluginType(PluginType::CHAT),
    fn() => Translator::get('info', 'Example chat plugin'),
    fn() => Translator::get('info', 'This plugin created only for demo purposes'),
    [
        "contactType" => $_ENV['LV_PLUGIN_CONTACT_TYPE'],
        "capabilities" => [
            "subject" => (bool) (int) $_ENV['LV_PLUGIN_CAPABILITY_SUBJECT'],
            "typing" => false,
            "messages" => [
                "formats" => empty($_ENV['LV_PLUGIN_MESSAGE_FORMATS']) ? ['text'] : explode(' ', $_ENV['LV_PLUGIN_MESSAGE_FORMATS']),
                "incoming" => (bool) (int) $_ENV['LV_PLUGIN_MESSAGE_INCOMING'],
                "outgoing" => (bool) (int) $_ENV['LV_PLUGIN_MESSAGE_OUTGOING'],
                "writeFirst" => (bool) (int) $_ENV['LV_PLUGIN_MESSAGE_WRITE_FIRST'],
                "statuses" => [
                    "sent",
                    "delivered",
                    "read",
                    "error"
                ]
            ],
            "attachments" => empty($_ENV['LV_PLUGIN_ATTACHMENTS']) ? [] : explode(' ', $_ENV['LV_PLUGIN_ATTACHMENTS']),
        ]
    ],
    new Developer(
        'LEADVERTEX',
        'support@salesrender.com',
        'salesrender.com',
    )
);

$uploader = new LocalUploadAction(['*' => 10 * 1024 * 1024]);
UploadersContainer::addDefaultUploader($uploader);
UploadersContainer::addCustomUploader('image', $uploader);
UploadersContainer::addCustomUploader('voice', $uploader);

# 4. Configure settings form
Settings::setForm(fn() => new SettingsForm());

# 5. Configure form autocompletes (or remove this block if dont used)
AutocompleteRegistry::config(function (string $name) {
//    switch ($name) {
//        case 'status': return new StatusAutocomplete();
//        case 'user': return new UserAutocomplete();
//        default: return null;
//    }
});

# 6. Configure ChatQueueHandleCommand
ChatSendQueueHandleCommand::config(new ChatSender());

# 7. If plugin receive messages via gateway from:
# - webhook: create any custom action that implement \SalesRender\Plugin\Core\Actions\ActionInterface and add it by
# extends WebAppFactory or in `public/index.php`. In your action your should get webhook data and convert it into
# \SalesRender\Plugin\Core\Chat\Components\Chat\Chat, after that call Chat::send()
#
# - API: create any custom console command @see https://symfony.com/doc/current/components/console.html and add it by
# extends ConsoleAppFactory or in `console.php`. Also, you should add your command in cron. Your command should get data
# from gateway API, convert it into \SalesRender\Plugin\Core\Chat\Components\Chat\Chat and call Chat::send()