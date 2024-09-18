<?php
namespace Skn036\Gmail\Filters;

class LogicallyOperableField
{
    /**
     * filters for the field
     * @var string|array<string>
     */
    public $filters;

    /**
     * Summary of operator
     * possible values are 'AND' or 'OR'
     *
     * @var string|null
     */
    public $operator;

    public function __construct($filterOrFilters, $operator = null)
    {
        if (!is_array($filterOrFilters)) {
            $filterOrFilters = [$filterOrFilters];
        }
        $this->filters = $filterOrFilters;
        $this->operator = $operator ?: 'OR';
    }
}
