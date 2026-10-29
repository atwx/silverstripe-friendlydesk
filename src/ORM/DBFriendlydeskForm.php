<?php

namespace Atwx\Friendlydesk\ORM;

use Atwx\Friendlydesk\Forms\FriendlydeskFormField;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Public URL of a friendlydesk form, edited with FriendlydeskFormField.
 *
 * In templates: $MyForm.Embed renders the widget (form title hidden),
 * $MyForm.EmbedWithTitle including the title.
 */
class DBFriendlydeskForm extends DBVarchar
{
    private static array $casting = [
        'Embed' => 'HTMLFragment',
        'EmbedWithTitle' => 'HTMLFragment',
    ];

    public function __construct(?string $name = null, int $size = 1000, array $options = [])
    {
        parent::__construct($name, $size, $options);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?\SilverStripe\Forms\FormField
    {
        return FriendlydeskFormField::create($this->name, $title);
    }

    /**
     * Script of the widget, served by the host of the form
     */
    public function WidgetURL(): ?string
    {
        $parts = parse_url((string) $this->getValue());
        if (!isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . '/widget.js';
    }

    public function Embed(): string
    {
        return $this->renderEmbed(true);
    }

    public function EmbedWithTitle(): string
    {
        return $this->renderEmbed(false);
    }

    private function renderEmbed(bool $hideTitle): string
    {
        if (!$this->exists() || !$this->WidgetURL()) {
            return '';
        }

        return (string) ArrayData::create([
            'URL' => $this->getValue(),
            'WidgetURL' => $this->WidgetURL(),
            'HideTitle' => $hideTitle,
        ])->renderWith('Atwx\\Friendlydesk\\Embed');
    }
}
