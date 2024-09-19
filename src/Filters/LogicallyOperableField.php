<?php
namespace Skn036\Gmail\Filters;

class LogicallyOperableField
{
    /**
     * filters for the field
     * @var array<string>
     */
    public $filters;

    /**
     * Summary of operator
     * possible values are 'AND' or 'OR'
     *
     * @var string|null
     */
    public $operator;

    /**
     * Summary of __construct
     *
     * @param string|array $filterOrFilters
     * @param string|null $operator
     */
    public function __construct(string|array $filterOrFilters, string|null $operator = null)
    {
        if (!is_array($filterOrFilters)) {
            $filterOrFilters = [$filterOrFilters];
        }
        $this->filters = $filterOrFilters;
        $this->operator = $operator ?: 'OR';
    }
}
