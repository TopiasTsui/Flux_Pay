<?php

declare(strict_types=1);

namespace App\Orchid\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Provides standard applyFilter / clearFilter handlers for list screens that
 * use App\Orchid\Layouts\Shared\FilterPanel.
 *
 * The screen must define `protected function filterRoute(): string` returning
 * the Laravel route name to redirect to.
 */
trait HasFilters
{
    /**
     * Read submitted `filter` form and redirect with filter query params.
     */
    public function applyFilter(Request $request): RedirectResponse
    {
        $filter = (array) $request->input('filter', []);
        $cleaned = $this->cleanFilter($filter);

        return redirect()->route($this->filterRoute(), $cleaned === [] ? [] : ['filter' => $cleaned]);
    }

    /**
     * Redirect to the same route without any filter params.
     */
    public function clearFilter(): RedirectResponse
    {
        return redirect()->route($this->filterRoute());
    }

    /**
     * Recursively remove empty values ('', null, []) from the filter array.
     */
    protected function cleanFilter(array $filter): array
    {
        $out = [];

        foreach ($filter as $k => $v) {
            if (is_array($v)) {
                $nested = $this->cleanFilter($v);
                if ($nested !== []) {
                    $out[$k] = $nested;
                }
            } elseif ($v !== '' && $v !== null) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    /**
     * Screen must declare the route name used for filter redirects.
     */
    abstract protected function filterRoute(): string;
}
