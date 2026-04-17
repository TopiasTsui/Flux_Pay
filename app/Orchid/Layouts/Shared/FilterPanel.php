<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Shared;

use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Accordion;
use Orchid\Support\Facades\Layout;

/**
 * Reusable collapsible filter panel.
 *
 * Renders a Bootstrap accordion (default collapsed). When collapsed the header
 * shows the number of active filters plus a short text summary; expanded, the
 * body contains the filter fields plus "Apply" and "Clear" buttons.
 *
 * Convention:
 * - Filter fields bind to dot-notation keys like `filter.status`, `filter.date`
 *   so the submitted form produces ?filter[status]=…&filter[date][start]=…
 * - The owning Screen uses the HasFilters trait to provide applyFilter() /
 *   clearFilter() methods that redirect with the filter query params.
 */
class FilterPanel
{
    /**
     * Build the accordion layout.
     *
     * @param array              $fields       Orchid Field instances already populated with current values.
     * @param array<string,mixed> $summary     Human-readable label => value pairs for active filters (empty = no badge).
     * @param string             $applyMethod  Screen method name invoked by the Apply button.
     * @param string             $clearMethod  Screen method name invoked by the Clear button.
     */
    public static function make(
        array $fields,
        array $summary = [],
        string $applyMethod = 'applyFilter',
        string $clearMethod = 'clearFilter',
    ): Accordion {
        $title = self::buildTitle($summary);

        return Layout::accordion([
            $title => [
                Layout::rows([
                    ...$fields,
                    Button::make(__('Apply'))
                        ->icon('bs.funnel-fill')
                        ->type(\Orchid\Support\Color::PRIMARY)
                        ->method($applyMethod),
                    Button::make(__('Clear'))
                        ->icon('bs.x-circle')
                        ->type(\Orchid\Support\Color::DEFAULT)
                        ->method($clearMethod),
                ]),
            ],
        ])->open([]);
    }

    private static function buildTitle(array $summary): string
    {
        $label = '<i class="bs-funnel"></i> ' . e(__('Filter'));

        if ($summary === []) {
            return $label;
        }

        $count = count($summary);
        $parts = [];

        foreach ($summary as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $parts[] = '<span class="me-3">' . e((string) $k) . ': <strong>' . e((string) $v) . '</strong></span>';
        }

        $summaryHtml = implode('', $parts);
        $maxLen = 160;
        if (mb_strlen(strip_tags($summaryHtml)) > $maxLen) {
            $summaryHtml = mb_substr(strip_tags($summaryHtml), 0, $maxLen) . '…';
            $summaryHtml = '<small class="text-muted">' . e($summaryHtml) . '</small>';
        } else {
            $summaryHtml = '<small class="text-muted">' . $summaryHtml . '</small>';
        }

        return $label
            . ' <span class="badge bg-primary ms-2">' . $count . '</span>'
            . ' ' . $summaryHtml;
    }
}
