<?php

namespace Atwx\Friendlydesk\Forms;

use Atwx\Friendlydesk\Api\FriendlydeskClient;
use RuntimeException;
use SilverStripe\Forms\DropdownField;

/**
 * Select one of the open friendlydesk forms; the value is the public URL of the form.
 *
 * A form that is no longer open stays selectable while it is the current value.
 */
class FriendlydeskFormField extends DropdownField
{
    private ?string $loadError = null;

    /**
     * Open forms (public URL => label), loaded once per request
     */
    private ?array $openForms = null;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, [], $value);
        $this->setHasEmptyDefault(true);
        $this->setEmptyString('Kein Formular');
    }

    public function getSource()
    {
        $source = $this->openForms();

        $value = (string) $this->Value();
        if ($value !== '' && !isset($source[$value])) {
            $source[$value] = sprintf('%s (nicht mehr offen)', $value);
        }

        return $source;
    }

    public function getDescription()
    {
        $this->openForms();
        $description = (string) parent::getDescription();
        if ($this->loadError) {
            $description = trim($description . ' ' . $this->loadError);
        }

        return $description;
    }

    private function openForms(): array
    {
        if ($this->openForms === null) {
            $this->openForms = [];
            try {
                foreach (FriendlydeskClient::singleton()->openForms() as $form) {
                    $this->openForms[$form['publicUrl']] = $this->formLabel($form);
                }
            } catch (RuntimeException $e) {
                $this->loadError = $e->getMessage();
            }
        }

        return $this->openForms;
    }

    private function formLabel(array $form): string
    {
        $label = (string) $form['title'];
        if (!empty($form['eventStart'])) {
            $label .= ' (' . date('d.m.Y', strtotime($form['eventStart'])) . ')';
        }

        return $label;
    }
}
