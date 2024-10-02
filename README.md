# Gmail Api For Laravel

This package is a wrapper around [gmail api](https://developers.google.com/gmail/api/guides) for Laravel from version 10 and upwards.

## Installation

You can install this package via composer.

```bash
composer require skn036/laravel-gmail-api
```

If you are using any google package from `skn036` for the first time, please run the following command to publish the config file. It will create a config file google.php in your config directory.

```bash
php artisan vendor:publish --provider="Skn036\Google\GoogleClientServiceProvider"
```

If this config file is already published typically though any other google package from `skn036`, no need to run above command. Just add the gmail related scopes.

### Adding gmail scope:

For the api to work properly, you should add at least `https://www.googleapis.com/auth/gmail.modify` scope from gmail. If you want to have full control over gmail like permanently deleting the resources, you should add the master permission `https://mail.google.com/` to the scopes. Typically scopes are added by updating `GOOGLE_SCOPES` variable in your `.env` file. Multiple scopes should be given as space separated. For more details about gmail scopes, please read [gmail documentation here](https://developers.google.com/gmail/api/auth/scopes).

## Google Authentication

Google authentication is done on a separate package, which will be installed automatically with this package. **Please check [skn036/laravel-google-client](https://github.com/skn-036/laravel-google-client) for detailed instruction on the google authentication**.

### Why authentication on a separate package?

Google uses only one authentication per app created in [google cloud console](https://console.cloud.google.com/apis/credentials/oauthclient) irrespective of how many services you use like gmail, calendar, drive, youtube etc. So keeping the authentication separate is always a good idea, if in case you want to add more services from google later on. [skn036/laravel-google-client](https://github.com/skn-036/laravel-google-client) is easily extendable. While using other services, you can directly start implementing any google service on top of it without creating new app on google console and redoing the authentication again.

```php
use Skn036\Google\GoogleClient;

class Youtube extends GoogleClient
{
    // GoogleClient will handle user authentication, keeping, refreshing and retrieving auth tokens.

    public function __construct(
        string|int|null $userId = null,
        ?string $usingAccount = null,
        ?array $config = null
    ) {
        parent::__construct($userId, $usingAccount, $config);
    }

    // implement youtube related functionality.
}
```

## Getting Started

`Skn036\Gmail\Gmail` is the entry point to interact with gmail api. You can access it by using the facade or directly creating an instance of it.

```php
// using facade
use Skn036\Gmail\Facades\Gmail;
$message = Gmail::messages()->get($messageId);

// if you want to pass constructor parameters like user id, email account (if using multiple accounts per user)
// or overriding config, use the base class instead.
use Skn036\Gmail\Gmail;
$gmail = new Gmail(auth()->id(), 'another_account@gmail.com', $customConfig);
```

## Reading Messages From Gmail

To read the list of messages from gmail.

```php
use Skn036\Gmail\Facades\Gmail;

$messageResponse = Gmail::messages()->maxResults(20)->list();
```

List response returns a instance of `Skn036\Gmail\Message\GmailMessagesList` which contains the list of messages, whether there's a next page, current page token, next page token and total messages. You can fetch the messages of the next page by calling `next` method or by passing the next page token to the `list` method. For fetching response of the previous page again, you should store the current page tokens and revert back to it if necessary.

`$messageResponse->messages` is a collection of `Skn036\Gmail\Message\GmailMessage`. Each message contains the necessary information about the message like id, thread id, from, to, cc, bcc, labels, subject, date, body, snippet, history id, references etc.

`$messageResponse->hasNextPage` is a boolean value which tells whether there's a next page or not.

`$messageResponse->nextPageToken` is a string value which contains the token to fetch the next page.

`$messageResponse->currentPageToken` is a string value which contains the token of the current page.

`$messageResponse->total` is a integer value which contains the total number of messages.

To fetch the messages of the next page, you can use `next` method or by passing the next page token to the `list` method.

```php
$nexPageResponse = $messageResponse->next();
// or
$nextPageResponse = $gmail->messages()->list($messageResponse->nextPageToken);
```

### Filtering Messages:

It is highly recommend to read gmail documentation on [Refine searches on gmail](https://support.google.com/mail/answer/7190) and [Searching for messages](https://developers.google.com/gmail/api/guides/filtering) have a clear idea about the search queries.

To include messages from spam and trash folders, you can use the `includeSpamTrash` method. For set the per page limit, you can use the `maxResults` method.

```php
$messageResponse = Gmail::messages()->includeSpamTrash()->maxResults(50)->list();
```

To filter messages by email address, you can use the `from`, `to`, `cc`, `bcc` method. If you want to filter by any field use `recipient` method instead. These methods expects a email address or a array of email addresses as first argument. If multiple email addresses are given, by default it will do logically `OR` operation. If you want to do `AND` operation, you can pass "AND" as the second argument. Of course you can chain as much as filters needed.

```php
$messages = Gmail::messages()
    ->from(['john@gmail.com', 'doe@gmail.com'])
    ->to('foo@bar.com')
    ->cc(['foo@gmail.com', 'bar@gmail.com'], 'AND')
    ->bcc(['test@gmail.com', 'another@gmail.com'], 'OR')
    ->recipient('another@gmail.com')
    ->list();
```

To filter messages by subject or any specific word or phrase in the message, include or exclude words on the search use `subject`, `search`, `includeWord` and `excludeWord` respectively.

```php
$messages = Gmail::messages()
    ->subject('Nice to meet you!!!')
    ->search('hello world')
    ->includeWord('Apple')
    ->excludeWord('Banana')
    ->list();
```

To filter by labels, you can use `label` method. It expects a label name or array of label names as first argument. If multiple labels are given, by default it will do logically `OR` operation. If you want to do `AND` operation, you can pass "AND" as the second argument.

```php
Gmail::messages()->label('inbox');
// or
Gmail::messages()->label(['inbox', 'important'], 'AND');
// or
Gmail::messages()->label(['draft', 'spam', 'trash', 'sent', 'starred']);
```

Samely, you can filter by `category` also.

```php
Gmail::messages()->category('primary');
// or
Gmail::messages()->category(['promotions', 'social'], 'AND');
// or
Gmail::messages()->category(['updates', 'reservations', 'purchases']);
```

If you want to filter messages whether has specific connection or star, use `has` method.

```php
Gmail::messages()->has('attachment');
// or
Gmail::messages()->has(['youtube', 'drive'], 'AND');
// or
Gmail::messages()->has(['yellow-star', 'orange-star', 'red-star']);
```

For searching messages by it's status, use `is` method.

```php
Gmail::messages()->is('unread');
// or
Gmail::messages()->is(['read', 'important'], 'AND');
// or
Gmail::messages()->is(['starred', 'muted']);
```

It is possible to search messages by it's location as well. Use `in` method for this.

```php
Gmail::messages()->in('anywhere');
// or
Gmail::messages()->in(['snoozed', 'spam'], 'AND');
// or
Gmail::messages()->in(['inbox', 'sent', 'draft']);
```

To search by message size, use `size`, `largerThan` and `smallerThan` methods.

```php
// by size
Gmail::messages()->size('1000000');
// size larger than
Gmail::messages()->largerThan('10M');
// size smaller than
Gmail::messages()->smallerThan('100K');
```

To find messages before or after any specific date, use `before` and `after` methods.

```php
Gmail::messages()->before('2021-01-01 23:10:30');
Gmail::messages()->after(Carbon::now()->subDays(10));
```

You can also use `olderThan` and `newerThan` methods to find messages older or newer than specific time.

```php
Gmail::messages()->olderThan('1d');
Gmail::messages()->newerThan('1y');
```

Instead, if you want to pass the raw query string to the search use `rawQuery` method. This will override all other filters. For more details on how to write raw query, please read [gmail documentation](https://developers.google.com/gmail/api/guides/filtering).

```php
$messages = Gmail::messages()
    ->rawQuery('in:sent after:1388552400 before:1391230800 subject: Hello!!!')
    ->includeSpamTrash()
    ->list()->messages;
```

### Getting a Single Message:

To get a single message, you can use the `get` method. It expects a message id as the first argument. It will return a instance of `\Skn036\Gmail\Message\GmailMessage`

```php
$message = Gmail::messages()->get($messageId);
```

## Sending Emails

This packages usages [symfony/mailer](https://symfony.com/doc/current/mailer.html) under the hood to send the emails. To send an email, first you should create a instance of `Skn036\Gmail\Message\Sendable\Email`.

```php
$email = Gmail::messages()->create(); // will create a instance of Skn036\Gmail\Message\Sendable\Email
```

you can add email "to" recipients by either `to` or `addTo` method. The difference between these two methods is, multiple chaining of `to` method will replace the previous recipients, while `addTo` will append the recipients.

```php
$email->to('hello@example.com', 'john@doe.com');
$email->addTo('foo@bar.com', 'test@gmail.com'); // these emails will append to previous
```

if you want to pass the name of the recipient along with the email address, you need to give each function argument as array of email and name. Instead, you can pass a instance of `Skn036\Gmail\Message\GmailMessageRecipient` also.

```php
$to = [
    ['test@gmail.com', 'Test User'],
    'foo@bar.com',
    new \Skn036\Gmail\Message\GmailMessageRecipient('john_doe@example.com', 'John Doe'),
];

$email->to(...$to);
```

Samely, you can add email "cc" and "bcc" recipients by using `cc`, `bcc`, `addCc` and `addBcc` methods.

```php
$email->cc(['test@gmail.com', 'Test User'], 'foo@bar.com');
$email->addCc(new \Skn036\Gmail\Message\GmailMessageRecipient('john_doe@example.com', 'John Doe'));

$email->bcc(['test@gmail.com', 'Test User'], ['foo@bar.com', 'Foo Bar']);
$email->addBcc('hello@example.com');
```

To set the name of the sender, you can use `setMyName` method. Receivers of the email will see this name as the sender of the email. If not set then it will take the name from synced google account's name.

```php
$email->setMyName('John Doe');
```

To retrieve the set recipients, you can use `getTo`, `getCc` and `getBcc` methods.

To set the subject of message use `subject` method.

```php
$email->subject('Hello World!!!');
```

You can set the priority of the message by `priority` method. It expects a integer value from 1 to 5, while 1 is the highest priority and 5 is the lowest. By default priority is set to 3 which is normal priority.

```php
$email->priority(1);
```

To set the body of the message, you can use `body` method. It supports both `html` and `text` string.

```php
$email->body('<div>Cool body</div>');
```

Instead if you want to set the body from the `view` file you can use `view` method. It expects the view file name and data to be passed to the view.

```php
$email->view('emails.welcome', ['name' => 'John Doe']);
```

You can also use markdown files to set the body of the message. Use `markdown` method for this.

```php
$email->markdown('emails.welcome', ['name' => 'John Doe']);
```

To add attachments to the email, you can use `attach` method. Multiple files can be attached by passing as individual arguments. Each argument expects path to file (w.r to the storage folder) or a instance of `\Illuminate\Http\UploadedFile`. Multiple chaining of the `attach` method will replace the previous attachments. to add attachments to the previous attachments, you can use `addAttachment` method instead.

```php
$files = $request->file('attachments');
$email->attach(...$files);

// or if the files exists in your storage folder, you can pass the file path
$email->attach('app/public/attachments/file1.pdf', 'app/public/attachments/file2.pdf');
```

To **embed inline images on email body**, you can use `embed` method. Multiple images can be embedded by passing as individual arguments. Each argument expects an array. First item of the array should be path to file (w.r to the storage folder) or a instance of `\Illuminate\Http\UploadedFile` and second item of the array should be the name of cid image. Image to embed properly, you must reference the cid name in the html body of the email like `<img src="cid:{name}">` or `<div background="cid:{name}"> ... </div>`.

Same as attachments, multiple chaining of the `embed` method will replace the previous embedded images. To add embedded images to the previous images, you can use `addEmbed` method instead.

```php
$files = $request->file('attachments');
$cidNames = $request->cid_names;

$embedFiles = array_map(
    function ($file, $cidName) {
        return [$file, $cidName];
    },
    $files,
    $cidNames
);
$email->embed(...$embedFiles);

// or if the files exists in your storage folder, you can pass the file path
$email->embed(
    ['app/public/attachments/watermark.png', 'watermark'],
    ['app/public/attachments/logo.png', 'logo']
);

// in the email body, you can reference the cid name like <img src="cid:logo"> or <body background="cid:watermark"> ... </body>
```

you can get the set properties by getter method generally like `getSubject`, `getPriority`, `getBody`, `getAttachments`, `getEmbeds` etc.

### Sending The Email:

When all of the properties are set, you can send the email by calling `send` method. It will return a instance of `\Skn036\Gmail\Message\GmailMessage`.

```php
$message = $email->send();
```

## The Message Instance `\Skn036\Gmail\Message\GmailMessage`

This instance contains all the information about the message as well as necessary helper methods to interact with the message.

```php
$message = Gmail::messages()->get($messageId);
// or
$message = Gmail::messages()->list()->messages->first();
```

**Message public properties are as follows:**

`$message->id` Id of the message. <br/>

`$message->threadId` Thread id of the message. <br />

`$message->headerMessageId` Message-id header on the message. <br />

`$message->replyTo` In-reply-to header of the message which is the header message id if the message is replied on some message. <br />

`$message->from` From recipient. <br />

`$message->to` Collection of to recipients. <br />

`$message->cc` Collection of cc recipients. By default, `bcc` is not public property. If you want to access it anyway, you can use `$message->getBcc()`. <br />

`$message->labels` Collection of label ids. <br />

`$message->subject` Subject of the message. <br />

`$message->date` Datetime of the message as a Carbon instance. <br />

`$message->body` Body of the message. By default it will return html body. If not available it will return text body. if you want to access both body individually you can call `$message->getHtmlBody()` or `$message->getTextBody()`. <br />

`$message->snippet` Snippet of the message. <br />

`$message->historyId` History id of the message. <br />

`$message->references` References message header. <br />

`$message->attachments` Collection of attachments.

### Working With Attachments:

`$message->attachments` is a Collection of `Skn036\Gmail\Message\GmailMessageAttachment`. Each attachment contains the following properties.

`$attachment->id` Id of the attachment. <br />

`$attachment->filename` Filename of the attachment. <br />

`$attachment->mimeType` Mime type of the attachment. <br />

`$attachment->size` Size of the attachment. <br />

`$attachment->data` Base64 encoded raw file content. By default it will be `null`. The content will be downloaded when called `save`, `download` or `getData` method.

For saving attachment to the file system, you can use `save` method. Path can be passed as function argument. This functionality usages Laravel's `Storage` facade. So path should be given w.r to public disk. If the path is not given, it will save the file to the path configured by `config('google.gmail.attachment_path')`.

```php
$attachment = $message->attachments->first();
$savedPath = $attachment->save('gmail-attachments');

// or you can directly save from the message instance
$savedPath = $message->saveAttachment($attachment->id, 'gmail-attachments');
```

To save all the attachments at once, you may use `saveAllAttachments` method.

```php
$savedPaths = $message->saveAllAttachments();
```

Rather than saving the attachment, if you want to download the attachment directly to your browser, you can use `download` method.

```php
public function downloadAttachment(Request $request) {
    $message = Gmail::messages()->get($request->message_id);

    $attachment = $message->attachments->first();
    return $attachment->download();

    // or
    return $message->downloadAttachment($request->attachment_id);
}
```

If you want to get the raw data of the attachment without saving or downloading, you can use `getData` method.

```php
$attachment = $attachment->getData();
$rawFile = $attachment->decodeBase64($attachment->data);
```

### Replying Or Forwarding Message:

Gmail has some criteria that should met in order to [messages being on the same thread](https://developers.google.com/gmail/api/guides/threads). To create a reply or forward message, you can use `createReply` and `createForward` methods. Both methods will create a instance of `Skn036\Gmail\Message\Sendable\Email` setting up necessary headers and subject to meet criteria from gmail. Then rest of the steps are same as [sending email](#sending-emails) mentioned above.

```php
// creating a forward email
$email = $message->createForward();

// creating a reply email
$email = $message->createReply();

$replyBody =
    "Hi! This is a reply to your message. \n\n" .
    'On ' .
    $message->date->format('l, F j, Y \a\t g:i A') .
    ', ' .
    "$message->from->name < $message->from->email >" .
    " wrote: \n" .
    $message->body;

$repliedMessage = $email
    ->to($message->from)
    ->body($replyBody)
    ->send();
```

### Creating Draft On The Message:

Creating draft on the message is similar to creating reply or forward. You can use `createDraft` method to create a draft on the message. It will create a instance of `Skn036\Gmail\Draft\Sendable\Draft` setting up necessary headers and subject to meet criteria from gmail. Then rest of the steps are same as [sending email](#sending-emails) mentioned above.

```php
// creating a reply/forward draft instance
$sendableDraft = $message->createDraft();

$draftBody =
    "Hi! This is a draft on the message. It is not necessarily to send immediately. \n\n" .
    'On ' .
    $message->date->format('l, F j, Y \a\t g:i A') .
    ', ' .
    "$message->from->name < $message->from->email >" .
    " wrote: \n" .
    $message->body;

$draft = $sendableDraft
    ->to($message->from)
    ->cc(...$message->cc->values()->all())
    ->body($draftBody)
    ->attach(...$attachments);

$draft = $draft->save(); // this will create a draft on the gmail
```

**NOTE:** Creating reply or forward will only set the necessary headers and subject to meet the criteria from gmail [for being in same thread](https://developers.google.com/gmail/api/guides/threads). It will not set the recipients, body or attachments. You should set them manually. This is because, most likely, these values will be updated on the frontend by user input before sending the email and implementation of these generally vary from project to project.

### Changing The Message Labels:

To add or remove labels from the message, you can use `addLabel` and `removeLabel` methods. It expects a label id or array of label ids as the first argument.

```php
// add labels
$message = $message->addLabels(['INBOX', 'IMPORTANT', 'UNREAD']);

// remove labels
$message = $message->removeLabels('SPAM');
```

If you want to add and remove on a single operation you can use `modifyLabels` method. It expects two arguments. First argument should be array of labels to add and second argument should be array of labels to remove.

```php
$message->modifyLabels(['INBOX', 'IMPORTANT', 'UNREAD'], ['SPAM']);
```

### Deleting Messages:

To move and remove messages from trash, you can use `trash` and `untrash` methods.

```php
$message->trash(); // moved to trash

$message->untrash(); // removed from trash
```

To delete the message permanently, you can use `delete` method. **NOTE:** Once the message is deleted, it can't be recovered. `https://mail.google.com/` scope is required to delete the message permanently.

```php
$message->delete();
```

You may call these methods directly on the message resource rather than on the message instance. All you need to do is to pass the message id as the first argument and rest of the arguments as mentioned above.

```php
$email = Gmail::messages()->createReply($messageId);

$message = Gmail::messages()->addLabel($messageId, 'IMPORTANT');

$message = Gmail::messages()->trash($messageId);
```

#### Accessing Raw Gmail Message:

If you want to access the raw message from gmail, you can use `getRawMessage` method. It will return the original message received from gmail which is a instance of `\Google_Service_Gmail_Message`.

```php
$rawMessage = $message->getRawMessage();
```

To get any header of the message you can use `getHeader` method. It expects the header name as the first argument.

```php
$rawTo = $message->getHeader('to');
```

### Working With Batches:

If you want to add or remove labels of multiple messages at once you use `batchAddLabels` and `batchRemoveLabels` methods. These methods expects a array or Collection of `message ids` or `Skn036\Gmail\Message\GmailMessage` as the first argument and label id or array of label ids on the second argument.

```php
$messageResource = Gmail::messages();
$messages = $messageResource->list()->messages;

// add labels
$messageResource->batchAddLabels($messages, ['IMPORTANT', 'UNREAD']);

// remove labels
$messageIds = $messages->pluck('id');
$messageResource->batchRemoveLabels($messageIds, 'SPAM');

// adding and removing labels on a single operation
$messageResource->batchModifyLabels($messages, ['IMPORTANT', 'UNREAD'], 'SPAM');
```

To delete multiple messages at once, you can use `batchDelete` method. **NOTE:** Once the message is deleted, it can't be recovered. `https://mail.google.com/` scope is required to delete the message permanently.

```php
$messageResource->batchDelete($messages);
```

## Gmail Drafts

Drafts are the message instances in gmail which are not sent yet. Typically they have a `DRAFT` label. To read the list of drafts from gmail.

```php
use Skn036\Gmail\Facades\Gmail;

$draftResponse = Gmail::drafts()->maxResults(20)->list();
```

List response returns a instance of `Skn036\Gmail\Draft\GmailDraftsList` which contains the list of drafts and other properties same as messages list. You can fetch the drafts of the next page by calling `next` method or by passing the next page token to the `list` method. For fetching response of the previous page again, you should store the current page tokens and revert back to it if necessary.

`$draftResponse->drafts` is a collection of `Skn036\Gmail\Draft\GmailDraft`. Each draft contains id and underlying message instance.

`$draftResponse->hasNextPage` is a boolean value which tells whether there's a next page or not.

`$draftResponse->nextPageToken` is a string value which contains the token to fetch the next page.

`$draftResponse->currentPageToken` is a string value which contains the token of the current page.

To fetch the drafts of the next page, you can use `next` method or by passing the next page token to the `list` method.

```php
$nexPageResponse = $draftResponse->next();
// or
$nextPageResponse = $gmail->drafts()->list($draftResponse->nextPageToken);
```

### Filtering Drafts:

Api for filtering drafts is [same as messages](#filtering-messages). You can use all the filters mentioned on the messages section to filter drafts.

```php
$drafts = Gmail::drafts()
    ->includeSpamTrash()
    ->maxResults(20)
    ->from(['foo@bar.com', 'john@doe.com'])
    ->in(['DRAFT', 'SPAM'])
    ->list()->drafts;
```

### Getting a Single Draft:

To get a single draft, you can use the `get` method. It expects a draft id as the first argument. It will return a instance of `\Skn036\Gmail\Draft\GmailDraft`

```php
$draft = Gmail::drafts()->get($draftId);
```

If you wish to access the underlying raw draft from gmail, you can use `getRawDraft` method. It will return the original draft received from gmail which is a instance of `\Google_Service_Gmail_Draft`.

```php
$rawDraft = $draft->getRawDraft();
```

### Creating, Updating And Sending Drafts:

To create a draft you can call `create` method. It will return a instance of `\Skn036\Gmail\Draft\Sendable\Draft`.

```php
$sendableDraft = Gmail::drafts()->create();
```

To edit a already saved draft, you can call `edit` method on draft instance. It will return a instance of `\Skn036\Gmail\Draft\Sendable\Draft`.

```php
// editing from the \Skn036\Gmail\Draft\GmailDraft instance
$draft = Gmail::drafts()->get($draftId);
$sendableDraft = $draft->edit();

// or edit the instance by passing the draft id
$sendableDraft = Gmail::drafts()->edit($draftOrDraftId);
```

Now you can edit the properties of the draft [like you do on the email](#sending-emails). Once you are done, you can call `save` method. It will save the draft on the gmail and return a instance of `\Skn036\Gmail\Draft\GmailDraft`. Or If you want to send the draft after editing, you can call `send` method. It will send the draft as email and return a instance of `\Skn036\Gmail\Message\GmailMessage`.

```php
$draft = $sendableDraft
    ->to('foo@bar.com', ['john@doe.com', 'John Doe'])
    ->cc('test@gmail.com')
    ->subject('First draft')
    ->body('Hello World!!!')
    ->attach(...$request->file('attachments'));

$savedDraft = $draft->save(); // saves the created or edited draft
// or
$sentMessage = $draft->send(); // sends the draft as email
```

**NOTE:** When you are editing the draft, you need to pass all the properties every time you edit, because gmail will replace the previous properties with the new one. So if you want to keep the previous properties, you should get the message from the draft instance and pass them again.

If you wish to send a already saved draft without further need of editing, you can use `send` method directly on the draft instance. It will send the last saved draft as email.

```php
$draft = Gmail::drafts()->get($draftId);
$message = $draft->send();
```

### Deleting Drafts:

You may delete a draft by calling `delete` method on the draft instance or passing the id to the draft. **NOTE:** Full mailbox permission is required for this action.

```php
$draft = Gmail::drafts()->get($draftId);
$draft->delete();

// or
Gmail::drafts()->delete($draftId);
```

### Updating Labels On Drafts:

Underlying message of `GmailDraft` is a instance of `GmailMessage`. So you can use the same methods mentioned on the [message section](#changing-the-message-labels) to add, remove or modify labels.

```php
$draft = Gmail::drafts()->get($draftId);
$message = $draft->message;

$message->addLabel('IMPORTANT');
```

## Managing Gmail Threads

Threads are the collection of messages grouped together which are on same conversation.

### Reading Threads From Gmail:

To read the list of threads from gmail.

```php
use Skn036\Gmail\Facades\Gmail;

$threadResponse = Gmail::threads()->maxResults(20)->list();
```

List response returns a instance of `Skn036\Gmail\Thread\GmailThreadsList` which contains the list of threads, whether there's a next page, current page token, next page token and total threads. You can fetch the threads of the next page by calling `next` method or by passing the next page token to the `list` method. For fetching response of the previous page again, you should store the current page tokens and revert back to it if necessary.

`$threadResponse->threads` is a collection of `Skn036\Gmail\Thread\GmailThread`. Each thread contains the necessary information like id, Collection of messages, snippet, history id etc.

`$threadResponse->hasNextPage` is a boolean value which tells whether there's a next page or not.

`$threadResponse->nextPageToken` is a string value which contains the token to fetch the next page.

`$threadResponse->currentPageToken` is a string value which contains the token of the current page.

`$threadResponse->total` is a integer value which contains the total number of threads.

To fetch the threads of the next page, you can use `next` method or by passing the next page token to the `list` method.

```php
$nexPageResponse = $threadResponse->next();
// or
$nextPageResponse = $gmail->threads()->list($threadResponse->nextPageToken);
```

### Filtering Threads:

Api for filtering threads is [same as messages](#filtering-messages). You can use all the filters mentioned on the messages section to filter threads.

```php
$threads = Gmail::threads()
    ->includeSpamTrash()
    ->maxResults(20)
    ->after(Carbon::now()->subDays(10))
    ->is(['unread'])
    ->list()->threads;
```

### Getting a Single Thread:

To get a single thread, you can use the `get` method. It expects a thread id as the first argument. It will return a instance of `\Skn036\Gmail\Thread\GmailThread`

```php
$thread = Gmail::threads()->get($threadId);
```

If you wish to access the underlying raw thread from gmail, you can use `getRawThread` method. It will return the original draft received from gmail which is a instance of `\Google_Service_Gmail_Thread`.

```php
$rawThread = $thread->getRawThread();
```

### Changing The Thread Labels:

To add or remove labels from the thread, you can use `addLabel` and `removeLabel` methods. It expects a label id or array of label ids as the first argument. Gmail will automatically modify labels of all the messages in the thread.

```php
// add labels
$thread->addLabels(['INBOX', 'IMPORTANT', 'UNREAD']);

// remove labels
$thread->removeLabels('SPAM');
```

If you want to add and remove on a single operation you can use `modifyLabels` method. It expects two arguments. First argument should be array of labels to add and second argument should be array of labels to remove.

```php
$thread->modifyLabels(['INBOX', 'IMPORTANT', 'UNREAD'], ['SPAM']);
```

### Deleting Threads:

To move and remove threads from trash, you can use `trash` and `untrash` methods.

```php
$thread->trash(); // moved to trash

$thread->untrash(); // removed from trash
```

To delete the thread along with it's messages permanently, you can use `delete` method. **NOTE:** Once the thread is deleted, it can't be recovered. `https://mail.google.com/` scope is required to delete the thread permanently.

```php
$thread->delete();
```

As usual, you may call these methods directly on the thread resource rather than on the `Skn036\Gmail\Thread\GmailThread` instance. All you need to do is to pass the thread id as the first argument and rest of the arguments as mentioned above.

```php
$thread = Gmail::threads()->addLabel($threadId, 'IMPORTANT');

$thread = Gmail::threads()->trash($threadId);
```

## Working With Gmail Labels

To read the list of labels from gmail.

```php
use Skn036\Gmail\Facades\Gmail;

$labelResponse = Gmail::labels()->list();
```

List response returns a instance of `Skn036\Gmail\Label\GmailLabelsList` which contains the list of threads.

`$labelResponse->labels` is a collection of `Skn036\Gmail\Label\GmailLabel`. Each label contains the necessary information like id, name, type, labelListVisibility, messageListVisibility, textColor, backgroundColor, messagesTotal, messagesUnread, threadsTotal, threadsUnread etc.

### Getting a Single Label:

To get a single label, you can use the `get` method. It expects a label id as the first argument. It will return a instance of `\Skn036\Gmail\Label\GmailLabel`

```php
$label = Gmail::labels()->get($labelId);
```

If you wish to access the underlying raw label from gmail, you can use `getRawLabel` method. It will return the original draft received from gmail which is a instance of `\Google_Service_Gmail_Label`.

```php
$rawLabel = $draft->getRawLabel();
```

### Creating Label:

To create a new label on gmail, you can use `create` method. It expects a array as first argument. The array can contain the following keys: `name`, `labelListVisibility`, `messageListVisibility`, `textColor`, `backgroundColor`. Only `name` is mandatory. Rest of the keys are optional. for more info about this parameters and their valid values, please read [gmail documentation on labels](https://developers.google.com/gmail/api/reference/rest/v1/users.labels).

```php
$label = Gmail::labels()->create([
    'name' => 'Test Label',
    'messageListVisibility' => 'show',
    'labelListVisibility' => 'labelShow',
    'textColor' => '#434343',
    'backgroundColor' => '#43d692',
]);
```

### Updating Label:

To update a label, you can use `update` method. It expects a label id as the first argument and array as the second argument. The array can contain the following keys: `name`, `labelListVisibility`, `messageListVisibility`, `textColor`, `backgroundColor`. All of the keys are optional.

```php
$label = Gmail::labels()->update('Label_20', [
    'textColor' => '#000000',
    'backgroundColor' => '#cca6ac',
]);
```

### Deleting Label:

To delete a label you can use `delete` method. It expects a label id as the first argument.

```php
Gmail::labels()->delete('Label_20');
```

## Watching Changes On Gmail Mailbox

If you wish to watch for changes on the synced mailboxes like messages added/deleted or labels added/deleted to the resources, you should start watching the mailbox by calling `start` method on `watch` resource.

Gmail provides gmail push notification via [google pubsub](https://console.cloud.google.com/cloudpubsub/topic/list). You should give gmail specific permissions to the pubsub topic and add a subscription. You should select response type to `push` on subscription and give the endpoint of you app where you expect the notifications. For more details on how to setup the pub sub, please read [gmail documentation on push notifications](https://developers.google.com/gmail/api/guides/push). After all the setup is done, you should add the topic name to `GOOGLE_PUB_SUB_TOPIC` on the `.env` file.

```php
$watchResponse = Gmail::watch()->start(); // starting the watch on the mailbox

Gmail::watch()->stop(); // stopping the watch on the mailbox
```

If you want to watch for changes on the specific labels, you can pass the label ids as an array the first argument to the `start` method and on the second argument you can pass 'include' or 'exclude' depending to your expectation. For more info, please check [gmail documentation on watch](https://developers.google.com/gmail/api/reference/rest/v1/users/watch).

```php
$watchResponse = Gmail::watch()->start(['INBOX', 'IMPORTANT'], 'include'); // starting the watch on specific label changes only
```

To get continuous push notification from gmail, you should call the watch `start` everyday for all synced gmail accounts via a cronjob.

if your watch is working correctly, you should receive a notification on the endpoint (post request) you have given on the pub sub subscription. On the request body you will receive a json and base64 encoded data. If you extract, it will give `historyId` and `emailAddress` of the synced account.

```php
$response = json_decode($request->getContent(), true);
$data = json_decode(base64_decode($response['message']['data']), true);
```

You should keep a track of the last used `historyId` and fetch all histories from the last `historyId` by using `histories` resource mentioned below. If you are syncing gmail mailbox to your local database, you can use these histories to keep your database updated.

## Gmail Histories

To read histories from gmail you can use `list` method on histories resource. You must give the start history id to fetch the histories from that point. If you don't pass this parameter, it will throw an exception. You can set this parameter by calling `startHistoryId` method.

```php
$historyResponse = Gmail::histories()->startHistoryId('123456')->list();
```

if you wish to fetch the histories of specific label or action, you can call `labelId` and `historyTypes` method respectively. You may also set the maximum number of histories to fetch by calling `maxResults` method.

```php
$historyResponse = Gmail::histories()
    ->startHistoryId('123456')
    ->labelId('INBOX')
    ->historyTypes(['messageAdded', 'labelRemoved'])
    ->maxResults(200)
    ->list();
```

List response returns a instance of `Skn036\Gmail\History\GmailHistoriesList` which contains the list of histories, whether there's a next page, current page token, next page token. You can fetch the histories of the next page by calling `next` method or by passing the next page token to the `list` method.

`$historyResponse->histories` is a collection of `Skn036\Gmail\History\GmailHistory`.

`$historyResponse->hasNextPage` is a boolean value which tells whether there's a next page or not.

`$historyResponse->nextPageToken` is a string value which contains the token to fetch the next page.

`$historyResponse->currentPageToken` is a string value which contains the token of the current page.

To fetch the histories of the next page, you can use `next` method or by passing the next page token to the `list` method.

```php
$nexPageResponse = $historyResponse->next();
// or
$nextPageResponse = $gmail->histories()->list($historyResponse->nextPageToken);
```

if you want to fetch all the histories and merge them into a collection, you can use `while` loop of PHP.

```php
$historyResponse = Gmail::histories()->startHistoryId('123456')->list();
$histories = $historyResponse->histories;

while ($historyResponse->hasNextPage) {
    $historyResponse = $historyResponse->next();
    $histories = $histories->merge($historyResponse->histories);
}
```

Each history is a instance of `Skn036\Gmail\History\GmailHistory`. It contains the necessary information like id, action, messagesAdded, messagesDeleted, labelsAdded, labelsRemoved etc.

`$history->id` id of the history

`$history->messagesAdded` is a array of `\Google_Service_Gmail_HistoryMessageAdded`. It will contain data if message added on the history.

`$history->messagesDeleted` is a array of `\Google_Service_Gmail_HistoryMessageDeleted`. It will contain data if message deleted on the history.

`$history->labelsAdded` is a array of `\Google_Service_Gmail_HistoryLabelAdded`. It will contain data if label added on the history.

`$history->labelsRemoved` is a array of `\Google_Service_Gmail_HistoryLabelRemoved`. It will contain data if label removed on the history.

`$history->messages` is a collection of `\Google_Service_Gmail_Message`.

`$history->action` is a string contains the action. enum values are either of `'message-added'` `'message-deleted'` `'labels-added'` `'labels-removed'`. If there's no action of the history it should return `null`. Checking this property is a good way to identify that if the history has any action or not.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
