<?php
/**
 * Created for plugin-core-dialog
 * Date: 30.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

use Leadvertex\Plugin\Components\Db\Components\Connector;
use Leadvertex\Plugin\Components\Form\Autocomplete\AutocompleteRegistry;
use Leadvertex\Plugin\Components\Info\Developer;
use Leadvertex\Plugin\Components\Info\Info;
use Leadvertex\Plugin\Components\Info\PluginType;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Actions\Upload\LocalUploadAction;
use Leadvertex\Plugin\Core\Actions\Upload\UploadersContainer;
use Leadvertex\Plugin\Core\Dialog\SendMessageQueue\DialogQueueHandleCommand;
use Leadvertex\Plugin\Instance\Dialog\Forms\SettingsForm;
use Leadvertex\Plugin\Instance\Dialog\Sender\DialogSender;
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
    new PluginType(PluginType::PBX),
    fn() => Translator::get('info', 'Example dialog plugin'),
    fn() => Translator::get('info', 'This plugin created only for demo purposes'),
    [
        "contactType" => $_ENV['LV_PLUGIN_CONTACT_TYPE'],
        "capabilities" => [
            "subject" => (bool) (int) $_ENV['LV_PLUGIN_CAPABILITY_SUBJECT'],
            "typing" => false,
            "messages" => [
                "formats" => explode(' ', $_ENV['LV_PLUGIN_MESSAGE_FORMATS']),
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
            "attachments" => explode(' ', $_ENV['LV_PLUGIN_ATTACHMENTS']),
        ]
    ],
    new Developer(
        'LEADVERTEX',
        'support@leadvertex.com',
        'leadvertex.com',
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

# 6. Configure DialogQueueHandleCommand
DialogQueueHandleCommand::config(new DialogSender());

# 7. If plugin receive messages via gateway from:
# - webhook: create any custom action that implement \Leadvertex\Plugin\Core\Actions\ActionInterface and add it by
# extends WebAppFactory or in `public/index.php`. In your action your should get webhook data and convert it into
# \Leadvertex\Plugin\Core\Dialog\Components\Dialog\Dialog, after that call Dialog::send()
#
# - API: create any custom console command @see https://symfony.com/doc/current/components/console.html and add it by
# extends ConsoleAppFactory or in `console.php`. Also, you should add your command in cron. Your command should get data
# from gateway API, convert it into \Leadvertex\Plugin\Core\Dialog\Components\Dialog\Dialog and call Dialog::send()