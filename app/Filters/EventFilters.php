<?php

namespace App\Filters;

class EventFilters extends QueryFilter
{
    // sorting
    public function order(string $order = 'asc')
    {
        return $this->builder->orderBy('date', $order);
    }

    // filtering
    public function format(string $format)
    {
        return $this->builder->where('format', '=', $format);
    }
    public function theme(string $theme)
    {
        return $this->builder->where('theme', '=', $theme);
    }
    public function fromDate($date)
    {
        return $this->builder->where('date', '>=', $date);
    }
    public function toDate($date)
    {
        return $this->builder->where('date', '<=', $date);
    }

    // paginate
    public function page($num)
    {
        $this->page_num = $num;
    }
    public function per_page($num)
    {
        $this->per_page_num = $num;
    }
}
