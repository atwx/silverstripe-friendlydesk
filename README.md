# Silverstripe friendlydesk

Select and embed [friendlydesk](https://friendlydesk.eu) forms in Silverstripe.

## Configuration

```env
FRIENDLYDESK_URL="https://<tenant>.friendlydesk.eu"
FRIENDLYDESK_TOKEN="fd_…"   # API key of the tenant (Team → API-Schlüssel), role "Nur lesen" is enough
```

## Usage

```php
private static array $db = [
    'RegistrationForm' => 'FriendlydeskForm',
];
```

The field is scaffolded as a dropdown with the forms that currently accept submissions
(title and event date). The stored value is the public URL of the form; a form that closed
later stays selected.

In templates:

```
$RegistrationForm.Embed           <%-- widget, form title hidden --%>
$RegistrationForm.EmbedWithTitle  <%-- widget with the form title --%>
$RegistrationForm                 <%-- the URL --%>
```

The widget is styled with CSS variables, e.g.

```css
.friendlydesk-form { --ff-primary: #1c3b80; --ff-radius: 0; }
```
