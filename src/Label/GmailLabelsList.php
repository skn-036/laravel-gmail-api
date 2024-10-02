<?php
namespace Skn036\Gmail\Label;

use Illuminate\Support\Collection;

class GmailLabelsList
{
    /**
     * Gmail labels
     * @var Collection<GmailLabel>
     */
    public $labels;

    /**
     * Summary of __construct
     * @param Collection<GmailLabel>|array<GmailLabel> $labels
     */
    public function __construct($labels)
    {
        $this->labels = collect($labels);
    }
}
