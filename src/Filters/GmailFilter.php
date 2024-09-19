<?php
namespace Skn036\Gmail\Filters;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GmailFilter
{
    /**
     * The fields which are OR/AND able in gmail q search params
     * Array key is the field name in this class and value is the field name in gmail q
     * Though some fields supports only one value and logical operation is not needed, for better maintainability and easiness added here
     *
     * @var array
     */
    const LOGICALLY_OPERABLE_FIELDS = [
        'qFrom' => 'from',
        'qTo' => 'to',
        'qCc' => 'cc',
        'qBcc' => 'bcc',
        'qRecipient' => 'list',
        'qSubject' => 'subject',
        'qLabel' => 'label',
        'qCategory' => 'category',
        'qHas' => 'has',
        'qIs' => 'is',
        'qIn' => 'in',
        'qFilename' => 'filename',
        'qSize' => 'size',
        'qSmaller' => 'smaller',
        'qLarger' => 'larger',
        'qOlder' => 'older_than',
        'qNewer' => 'newer_than',
    ];

    /**
     * includeSpamTrash on gmail filter params
     * @var bool
     */
    protected $includeSpamTrash;

    /**
     * Current page token of the list response
     * @var string|null
     */
    protected $currentPageToken;

    /**
     * Next page token of the list response
     * @var string|null
     */
    protected $nextPageToken;

    /**
     * Per page of the list response
     * @var int
     */
    protected $maxResults = 20;

    /**
     * Search string for the filter on gmail
     * for more info on gmail search: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var string
     */
    protected $q = '';

    /**
     * list field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qRecipient;

    /**
     * from field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qFrom;

    /**
     * to field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qTo;

    /**
     * cc field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qCc;

    /**
     * bcc field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qBcc;

    /**
     * subject field in gmail search string
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qSubject;

    /**
     * label field in gmail search string
     * valid values are label ids on the gmail
     * for more info: https://developers.google.com/gmail/api/guides/labels
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qLabel;

    /**
     * category field in gmail search string
     * valid values are: primary, social, promotions, updates, forums, reservations, purchases
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qCategory;

    /**
     * has field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qHas;

    /**
     * is field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qIs;

    /**
     * in field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qIn;

    /**
     * in field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qFilename;

    /**
     * size field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qSize;

    /**
     * smaller field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qSmaller;

    /**
     * larger field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qLarger;

    /**
     * older_than field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qOlder;

    /**
     * newer_than field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var Collection<int, LogicallyOperableField>|null
     */
    protected $qNewer;

    /**
     * before field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var float|int|string|null
     */
    protected $qBefore;

    /**
     * after field in gmail search string
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var float|int|string|null
     */
    protected $qAfter;

    /**
     * Matches exact word or phrase
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var string|null
     */
    protected $qMatchExact;

    /**
     * Include a word exactly
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var string|null
     */
    protected $qIncludeWord;

    /**
     * Exclude a word
     * for more info: https://developers.google.com/gmail/api/guides/filtering
     *
     * @var string|null
     */
    protected $qExcludeWord;

    /**
     * Whether has a next page
     * @return bool
     */
    public function hasNextPage()
    {
        return !!$this->nextPageToken;
    }

    /**
     * Get current page token
     * @return string|null
     */
    public function getCurrentPageToken()
    {
        return $this->currentPageToken;
    }

    /**
     * Get next page token
     * @return string|null
     */
    public function getNextPageToken()
    {
        return $this->nextPageToken;
    }

    /**
     * sets param to fetch spam and trash threads/messages on list response
     *
     * @return static
     */
    public function includeSpamTrash()
    {
        return $this->setFilterParam('includeSpamTrash', true);
    }

    /**
     * max no of messages/threads per page
     *
     * @param string|int|null $perPage
     * @return static
     */
    public function maxResults(string|int|null $perPage)
    {
        if ($perPage && (int) $perPage > 0) {
            $this->setFilterParam('maxResults', $perPage);
        }
        return $this;
    }

    /**
     * Sets "list" field in gmail search string
     * Search by email address in the recipient list
     *
     * @param string|array<string> $emailOrEmails for own email, special parameter 'me' can be used instead of email
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function recipient(string|array $emailOrEmails, string|null $operator = null)
    {
        return $this->setFilterParam('qRecipient', $emailOrEmails, $operator);
    }

    /**
     * Sets "from" field in gmail search string
     *
     * @param string|array<string> $emailOrEmails for own email, special parameter 'me' can be used instead of email
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function from(string|array $emailOrEmails, string|null $operator = null)
    {
        return $this->setFilterParam('qFrom', $emailOrEmails, $operator);
    }

    /**
     * Sets "to" field in gmail search string
     *
     * @param string|array<string> $emailOrEmails for own email, special parameter 'me' can be used instead of email
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function to(string|array $emailOrEmails, string|null $operator = null)
    {
        return $this->setFilterParam('qTo', $emailOrEmails, $operator);
    }

    /**
     * Sets "cc" field in gmail search string
     *
     * @param string|array<string> $emailOrEmails for own email, special parameter 'me' can be used instead of email
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function cc(string|array $emailOrEmails, string|null $operator = null)
    {
        return $this->setFilterParam('qCc', $emailOrEmails, $operator);
    }

    /**
     * Sets "bcc" field in gmail search string
     *
     * @param string|array<string> $emailOrEmails for own email, special parameter 'me' can be used instead of email
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function bcc(string|array $emailOrEmails, string|null $operator = null)
    {
        return $this->setFilterParam('qBcc', $emailOrEmails, $operator);
    }

    /**
     * Sets "subject" field in gmail search string
     * Note: If this method is called multiple times, only the last call will be considered
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject)
    {
        // preventing subject for setting multiple times as gmail only expects one subject
        if ($this->qSubject instanceof Collection) {
            $this->qSubject = null;
        }
        return $this->setFilterParam('qSubject', $subject);
    }

    /**
     * Sets "label" field in gmail search string
     * valid values are label ids on the gmail
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|array<string> $labelOrLabels
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function label(string|array $labelOrLabels, string|null $operator = null)
    {
        return $this->setFilterParam('qLabel', $labelOrLabels, $operator);
    }

    /**
     * Sets "category" field in gmail search string
     * valid values are: primary, social, promotions, updates, forums, reservations, purchases
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|array<string> $categoryOrCategories
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function category(string|array $categoryOrCategories, string|null $operator = null)
    {
        return $this->setFilterParam('qCategory', $categoryOrCategories, $operator);
    }

    /**
     * Sets "has" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|array<string> $has
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function has(string|array $has, string|null $operator = null)
    {
        return $this->setFilterParam('qHas', $has, $operator);
    }

    /**
     * Sets "is" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|array<string> $is
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function is(string|array $is, string|null $operator = null)
    {
        return $this->setFilterParam('qIs', $is, $operator);
    }

    /**
     * Sets "in" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|array<string> $in
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function in(string|array $in, string|null $operator = null)
    {
        return $this->setFilterParam('qIn', $in, $operator);
    }

    /**
     * Sets "size" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $size
     * @return static
     */
    public function size(string $size)
    {
        // preventing setting multiple times as gmail only expects one
        if ($this->qSize instanceof Collection) {
            $this->qSize = null;
        }
        return $this->setFilterParam('qSize', $size);
    }

    /**
     * Sets "smaller" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $emailSize
     * @return static
     */
    public function smallerThan(string $emailSize)
    {
        // preventing setting multiple times as gmail only expects one
        if ($this->qSmaller instanceof Collection) {
            $this->qSmaller = null;
        }
        return $this->setFilterParam('qSmaller', $emailSize);
    }

    /**
     * Sets "larger" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $emailSize
     * @return static
     */
    public function largerThan(string $emailSize)
    {
        // preventing setting multiple times as gmail only expects one
        if ($this->qLarger instanceof Collection) {
            $this->qLarger = null;
        }
        return $this->setFilterParam('qLarger', $emailSize);
    }

    /**
     * Sets "older_than" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $dateDifference
     * @return static
     */
    public function olderThan(string $dateDifference)
    {
        // preventing setting multiple times as gmail only expects one
        if ($this->qOlder instanceof Collection) {
            $this->qOlder = null;
        }
        return $this->setFilterParam('qOlder', $dateDifference);
    }

    /**
     * Sets "newer_than" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $dateDifference
     * @return static
     */
    public function newerThan(string $dateDifference)
    {
        // preventing setting multiple times as gmail only expects one
        if ($this->qNewer instanceof Collection) {
            $this->qNewer = null;
        }
        return $this->setFilterParam('qNewer', $dateDifference);
    }

    /**
     * Sets "before" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|int|Carbon $date
     * @return static
     */
    public function before(string|int|Carbon $date)
    {
        $timestamp = Carbon::parse($date)->timestamp;
        return $this->setFilterParam('qBefore', $timestamp);
    }

    /**
     * Sets "after" field in gmail search string
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string|int|Carbon $date
     * @return static
     */
    public function after(string|int|Carbon $date)
    {
        $timestamp = Carbon::parse($date)->timestamp;
        return $this->setFilterParam('qAfter', $timestamp);
    }

    /**
     * Matches exact word or phrase
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $wordOrPhrase
     * @return static
     */
    public function matchExact(string $wordOrPhrase)
    {
        return $this->setFilterParam('qMatchExact', '"' . $wordOrPhrase . '"');
    }

    /**
     * Alias for matchExact
     *
     * @param string $wordOrPhrase
     * @return static
     */
    public function search(string $wordOrPhrase)
    {
        return $this->matchExact($wordOrPhrase);
    }

    /**
     * Include word exactly
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $word
     * @return static
     */
    public function includeWord(string $word)
    {
        return $this->setFilterParam('qIncludeWord', '+' . $word);
    }

    /**
     * Exclude word exactly
     * for more info: https://support.google.com/mail/answer/7190
     *
     * @param string $word
     * @return static
     */
    public function excludeWord(string $word)
    {
        return $this->setFilterParam('qIncludeWord', '-' . $word);
    }

    /**
     * Use this if you want to set the raw q string directly on gmail search
     * Note: if this method is called and q string is set, all other filters will be ignored
     *
     * @param string $q
     * @return static
     */
    public function rawQuery(string $q)
    {
        return $this->setFilterParam('q', $q);
    }

    /**
     * Sets any valid parameters on the current instance
     * we can set any valid parameter on the current instance using this method.
     * like rather than calling $message->includeSpamTrash(), we can call $message->setFilterParam('includeSpamTrash', true)
     * operator is applicable only if the field is logically operable. see static::LOGICALLY_OPERABLE_FIELDS
     *
     * @param string $param
     * @param mixed $value
     * @param string|null $operator possible values: OR, AND
     *
     * @return static
     */
    public function setFilterParam(string $param, $value, string|null $operator = null)
    {
        if (!property_exists($this, $param)) {
            return $this;
        }

        if (array_key_exists($param, static::LOGICALLY_OPERABLE_FIELDS)) {
            if (!$this->$param || !($this->$param instanceof Collection)) {
                $this->$param = collect([]);
            }

            $this->$param->push(new LogicallyOperableField($value, $operator));
        } else {
            $this->$param = $value;
        }

        return $this;
    }

    /**
     * prepares the q param on the gmail search according to the filters
     * properties starts with q are converted to q string
     *
     * @return string
     */
    private function prepareQ()
    {
        if ($this->q) {
            return $this->q;
        }

        $q = $this->convertAllLogicallyOperableFieldsToQString();

        if ($this->qBefore) {
            $q = $q . ' before:' . $this->qBefore;
        }
        if ($this->qAfter) {
            $q = $q . ' after:' . $this->qAfter;
        }
        if ($this->qMatchExact) {
            $q = $q . ' ' . $this->qMatchExact;
        }
        if ($this->qIncludeWord) {
            $q = $q . ' ' . $this->qIncludeWord;
        }
        if ($this->qExcludeWord) {
            $q = $q . ' ' . $this->qExcludeWord;
        }

        return $q;
    }

    /**
     * Reducing all logically operable fields to q string
     *
     * @param string $q
     * @return string
     */
    private function convertAllLogicallyOperableFieldsToQString($q = '')
    {
        foreach (static::LOGICALLY_OPERABLE_FIELDS as $field => $fieldQName) {
            if (!$this->$field || !($this->$field instanceof Collection)) {
                continue;
            }
            foreach ($this->$field as $fieldValue) {
                $q = $this->addLogicallyOperableFieldToQString($fieldValue, $fieldQName, $q);
            }
        }
        return $q;
    }

    /**
     * Append one logically operable field to q string
     *
     * @param string $q
     * @param mixed $fieldValue
     * @param string $fieldQName
     *
     * @return string
     */
    private function addLogicallyOperableFieldToQString($fieldValue, $fieldQName, $q = '')
    {
        if (!($fieldValue instanceof LogicallyOperableField)) {
            return $q;
        }
        if (!is_array($fieldValue->filters) || !count($fieldValue->filters)) {
            return $q;
        }

        if (trim($q)) {
            $q = $q . ' ';
        }

        $separator = $fieldValue->operator . ' ';
        foreach ($fieldValue->filters as $filter) {
            $q = $q . $fieldQName . ':' . $filter . ' ' . $separator;
        }
        $q = trim(trim($q, $separator));

        return $q;
    }

    /**
     * Returns filter params as expected by gmail list messages/threads request
     *
     * @return array
     */
    protected function prepareFilterParams()
    {
        $params = [];
        if ($this->includeSpamTrash) {
            $params['includeSpamTrash'] = $this->includeSpamTrash;
        }
        if ($this->currentPageToken) {
            $params['pageToken'] = $this->currentPageToken;
        }
        if ($this->maxResults) {
            $params['maxResults'] = (string) $this->maxResults;
        }

        $q = $this->prepareQ();
        if ($q) {
            $params['q'] = $q;
        }

        return $params;
    }
}
