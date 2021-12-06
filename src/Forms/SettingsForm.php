<?php
/**
 * Created for plugin-chat-example
 * Datetime: 03.03.2020 15:43
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Instance\Chat\Forms;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\IntegerDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\StringDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;

class SettingsForm extends Form
{

    public function __construct()
    {
        $nonNull = function ($value, FieldDefinition $definition, FormData $data) {
            $errors = [];
            if (is_null($value)) {
                $errors[] = Translator::get('settings', 'Field can not be empty');
            }
            return $errors;
        };
        parent::__construct(
            Translator::get('settings', 'Settings'),
            Translator::get('settings', 'Demo chat plugin'),
            [
                'main' => new FieldGroup(
                    Translator::get('settings', 'Main'),
                    null,
                    [
                        'prefix' => new StringDefinition(
                            Translator::get('settings', 'Answer prefix'),
                            null,
                            $nonNull,
                            'Reply to your message: «',
                        ),
                        'suffix' => new StringDefinition(
                            Translator::get('settings', 'Answer suffix'),
                            null,
                            $nonNull,
                            '»',
                        ),
                        'delay' => new IntegerDefinition(
                            Translator::get('settings', 'Delay (sec)'),
                            Translator::get('settings', 'Delay for answering and message status webhooks'),
                            function ($value, FieldDefinition $definition, FormData $data) {
                                $errors = [];
                                $value = (int) $value;
                                if (is_null($value)) {
                                    $errors[] = Translator::get('settings', 'Field can not be empty');
                                }

                                if ($value < 0) {
                                    $errors[] = Translator::get('settings', 'Delay can not be less, than zero');
                                }

                                if ($value > 10) {
                                    $errors[] = Translator::get('settings', 'Delay can not be more, than 10 seconds');
                                }

                                return $errors;
                            },
                            3
                        ),
                    ]
                ),
            ],
            Translator::get('settings', 'Save'),
        );
    }
}


