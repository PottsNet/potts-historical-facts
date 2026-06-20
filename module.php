<?php

declare(strict_types=1);

use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\Exceptions\HttpAccessDeniedException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleHistoricEventsInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return new class extends AbstractModule implements ModuleCustomInterface, ModuleHistoricEventsInterface, ModuleGlobalInterface, ModuleBlockInterface, ModuleConfigInterface {
    use ModuleConfigTrait;

    private const REGION_COOKIE = 'potts_history_region';
    private const AGE_MARKER = '__POTTS_HISTORY_AGE__';
    private const DEFAULTS = [
        'DEFAULT_REGION'       => 'en_AU',
        'SHOW_GLOBAL_SELECTOR' => '1',
        'SHOW_EVENT_AGES'      => '1',
        'MAX_LIFESPAN'         => '120',
    ];

    public function title(): string
    {
        return 'Potts Historical Facts';
    }

    public function description(): string
    {
        return 'Displays historical facts from CSV files using a visitor-selected region available from every page.';
    }

    public function customModuleAuthorName(): string
    {
        return 'Jason Potts';
    }

    public function customModuleVersion(): string
    {
        return '1.1.0-beta.1';
    }

    public function customModuleLatestVersion(): string
    {
        return $this->customModuleVersion();
    }

    public function customModuleLatestVersionUrl(): string
    {
        return '';
    }

    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/PottsNet/potts-historical-facts/issues';
    }

    public function customTranslations(string $language): array
    {
        return [];
    }

    public function headContent(): string
    {
        return '';
    }

    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdministrator($request);
        $this->layout = 'layouts/administration';
        View::registerNamespace('potts-historical-facts', $this->resourcesFolder() . 'views/');

        return $this->viewResponse('potts-historical-facts::admin/settings', [
            'title'      => I18N::translate('Potts Historical Facts settings'),
            'action_url' => route('module', [
                'module' => $this->name(),
                'action' => 'Admin',
            ]),
            'regions'    => $this->availableHistoryRegions(),
            'settings'   => $this->settings(),
            'saved'      => Validator::queryParams($request)->boolean('saved', false),
            'reset'      => Validator::queryParams($request)->boolean('reset', false),
            'version'    => $this->customModuleVersion(),
        ]);
    }

    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdministrator($request);

        $parsed = $request->getParsedBody();
        $data = is_array($parsed) ? $parsed : [];
        $task = isset($data['task']) && is_string($data['task']) ? $data['task'] : 'save';

        if ($task === 'reset') {
            foreach (self::DEFAULTS as $key => $value) {
                $this->setPreference($key, $value);
            }

            return redirect(route('module', [
                'module' => $this->name(),
                'action' => 'Admin',
                'reset'  => '1',
            ]));
        }

        $region = isset($data['default_region']) && is_string($data['default_region'])
            ? $this->normaliseHistoryCode($data['default_region'])
            : self::DEFAULTS['DEFAULT_REGION'];

        if ($this->csvPathForHistoryCode($region) === null) {
            $region = self::DEFAULTS['DEFAULT_REGION'];
        }

        $lifespan = isset($data['max_lifespan']) ? (int) $data['max_lifespan'] : (int) self::DEFAULTS['MAX_LIFESPAN'];
        $lifespan = max(80, min(150, $lifespan));

        $this->setPreference('DEFAULT_REGION', $region);
        $this->setPreference('MAX_LIFESPAN', (string) $lifespan);
        $this->setPreference('SHOW_GLOBAL_SELECTOR', isset($data['show_global_selector']) && (string) $data['show_global_selector'] === '1' ? '1' : '0');
        $this->setPreference('SHOW_EVENT_AGES', isset($data['show_event_ages']) && (string) $data['show_event_ages'] === '1' ? '1' : '0');

        return redirect(route('module', [
            'module' => $this->name(),
            'action' => 'Admin',
            'saved'  => '1',
        ]));
    }

    private function assertAdministrator(ServerRequestInterface $request): void
    {
        $user = Validator::attributes($request)->user();

        if (!Auth::isAdmin($user)) {
            throw new HttpAccessDeniedException();
        }
    }

    /** @return array<string,string> */
    private function settings(): array
    {
        return [
            'DEFAULT_REGION'       => $this->configuredDefaultHistoryRegion(),
            'SHOW_GLOBAL_SELECTOR' => $this->showGlobalSelector() ? '1' : '0',
            'SHOW_EVENT_AGES'      => $this->showEventAges() ? '1' : '0',
            'MAX_LIFESPAN'         => (string) $this->maximumLifespan(),
        ];
    }

    private function showGlobalSelector(): bool
    {
        return $this->getPreference('SHOW_GLOBAL_SELECTOR', self::DEFAULTS['SHOW_GLOBAL_SELECTOR']) === '1';
    }

    private function showEventAges(): bool
    {
        return $this->getPreference('SHOW_EVENT_AGES', self::DEFAULTS['SHOW_EVENT_AGES']) === '1';
    }

    private function maximumLifespan(): int
    {
        $value = (int) $this->getPreference('MAX_LIFESPAN', self::DEFAULTS['MAX_LIFESPAN']);

        return max(80, min(150, $value));
    }

    public function bodyContent(): string
    {
        $regions = $this->availableHistoryRegions();
        $selected = $this->selectedHistoryRegionForDisplay();
        $default_label = $this->defaultHistoryRegionLabel($regions);

        $options = [
            ['code' => 'auto', 'label' => 'Site default (' . $default_label . ')'],
        ];

        foreach ($regions as $code => $label) {
            $options[] = ['code' => $code, 'label' => $label];
        }

        $options_json = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $selected_json = json_encode($selected, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $cookie_json = json_encode(self::REGION_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $show_global_json = $this->showGlobalSelector() ? 'true' : 'false';

        $html = <<<'HTML'
<style>
.potts-history-global {
    position: relative;
    display: inline-flex;
    align-items: center;
    align-self: center;
    z-index: 1055;
}
.potts-history-global__button {
    appearance: none;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    margin: 0;
    line-height: 1.25;
    padding: .5rem .7rem;
    cursor: pointer;
    white-space: nowrap;
}
.potts-history-global__button::after {
    content: "";
    display: inline-block;
    margin-left: .35rem;
    vertical-align: .15em;
    border-top: .34em solid currentColor;
    border-right: .28em solid transparent;
    border-left: .28em solid transparent;
}
.potts-history-global__menu {
    position: absolute;
    top: calc(100% + .35rem);
    left: 50%;
    transform: translateX(-50%);
    width: min(22rem, calc(100vw - 2rem));
    max-height: min(28rem, 70vh);
    overflow: auto;
    display: none;
    padding: .45rem;
    margin: 0;
    list-style: none;
    background: #fffdf8;
    color: #18313b;
    border: 1px solid rgba(35, 48, 56, .18);
    border-radius: .75rem;
    box-shadow: 0 .85rem 2rem rgba(25, 42, 50, .22);
}
.potts-history-global.is-open .potts-history-global__menu {
    display: block;
}
.potts-history-global__option {
    display: block;
    width: 100%;
    border: 0;
    border-radius: .5rem;
    background: transparent;
    color: inherit;
    text-align: left;
    padding: .58rem .7rem;
    cursor: pointer;
}
.potts-history-global__option:hover,
.potts-history-global__option:focus-visible {
    background: #eef3ea;
    outline: 2px solid #185a71;
    outline-offset: -2px;
}
.potts-history-global__option[aria-current="true"] {
    background: #dfe8da;
    font-weight: 700;
}
.potts-history-global--fallback {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 1045;
    border: 1px solid rgba(35, 48, 56, .18);
    border-radius: 999px;
    background: #fffdf8;
    color: #18313b;
    box-shadow: 0 .5rem 1.4rem rgba(25, 42, 50, .18);
}
.potts-history-global--fallback .potts-history-global__menu {
    top: auto;
    bottom: calc(100% + .5rem);
    left: auto;
    right: 0;
    transform: none;
}
@media (max-width: 767.98px) {
    .potts-history-global__button {
        width: 100%;
        text-align: left;
    }
    .potts-history-global__menu {
        left: 0;
        transform: none;
    }
    .potts-history-global--fallback .potts-history-global__button {
        width: auto;
    }
}
</style>
<script>
(function () {
    const marker = '__POTTS_HISTORY_AGE__';
    const options = __POTTS_OPTIONS__;
    const selected = __POTTS_SELECTED__;
    const cookieName = __POTTS_COOKIE__;
    const enableGlobalSelector = __POTTS_SHOW_GLOBAL__;

    function replaceTextNode(node) {
        if (!node || !node.nodeValue || node.nodeValue.indexOf(marker) === -1) {
            return;
        }

        const text = node.nodeValue;
        const position = text.indexOf(marker);
        const title = text.substring(0, position).trim();
        const age = text.substring(position + marker.length).trim();

        if (title === '' || age === '') {
            return;
        }

        const fragment = document.createDocumentFragment();

        const titleElement = document.createElement('span');
        titleElement.className = 'potts-history-event-type';
        titleElement.textContent = title;
        fragment.appendChild(titleElement);

        const ageElement = document.createElement('div');
        ageElement.className = 'potts-history-event-age small text-muted mt-2';
        ageElement.textContent = age;
        fragment.appendChild(ageElement);

        node.parentNode.replaceChild(fragment, node);
    }

    function formatHistoryAgeLabels(root) {
        const walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT);
        const nodes = [];
        let node;

        while ((node = walker.nextNode())) {
            if (node.nodeValue.indexOf(marker) !== -1) {
                nodes.push(node);
            }
        }

        nodes.forEach(replaceTextNode);
    }

    function selectedLabel() {
        const match = options.find(function (item) { return item.code === selected; });
        return match ? match.label : 'Historical facts';
    }

    function saveRegion(code) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';

        if (code === 'auto') {
            document.cookie = cookieName + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
        } else {
            document.cookie = cookieName + '=' + encodeURIComponent(code) + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
        }

        window.location.reload();
    }

    function buildSelector() {
        const wrapper = document.createElement('div');
        wrapper.className = 'potts-history-global';
        wrapper.setAttribute('data-potts-history-selector', '1');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'potts-history-global__button';
        button.setAttribute('aria-haspopup', 'menu');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', 'potts-history-global-menu');
        button.textContent = 'History: ' + selectedLabel();

        const menu = document.createElement('div');
        menu.id = 'potts-history-global-menu';
        menu.className = 'potts-history-global__menu';
        menu.setAttribute('role', 'menu');

        options.forEach(function (item) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'potts-history-global__option';
            option.setAttribute('role', 'menuitem');
            option.tabIndex = -1;
            option.setAttribute('aria-current', item.code === selected ? 'true' : 'false');
            option.textContent = item.label;
            option.addEventListener('click', function () { saveRegion(item.code); });
            menu.appendChild(option);
        });

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const open = !wrapper.classList.contains('is-open');
            document.querySelectorAll('.potts-history-global.is-open').forEach(function (item) {
                item.classList.remove('is-open');
                const itemButton = item.querySelector('.potts-history-global__button');
                if (itemButton) itemButton.setAttribute('aria-expanded', 'false');
            });
            wrapper.classList.toggle('is-open', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                const current = menu.querySelector('[aria-current="true"]') || menu.querySelector('[role="menuitem"]');
                if (current) current.focus();
            }
        });

        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                wrapper.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                const current = menu.querySelector('[aria-current="true"]') || menu.querySelector('[role="menuitem"]');
                if (current) current.focus();
            }
        });

        menu.addEventListener('keydown', function (event) {
            const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
            const index = items.indexOf(document.activeElement);
            let next = index;

            if (event.key === 'ArrowDown') next = (index + 1) % items.length;
            else if (event.key === 'ArrowUp') next = (index - 1 + items.length) % items.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = items.length - 1;
            else if (event.key === 'Escape') {
                event.preventDefault();
                wrapper.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
                button.focus();
                return;
            } else {
                return;
            }

            event.preventDefault();
            if (items[next]) items[next].focus();
        });

        wrapper.appendChild(button);
        wrapper.appendChild(menu);
        return wrapper;
    }

    function textOf(element) {
        return (element && element.textContent ? element.textContent : '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function findLanguageControl() {
        const stable = document.querySelector('.wt-language-menu, [data-wt-menu="language"], [class*="language-menu"], [class*="wt-language"], #language-menu');

        if (stable) {
            return stable;
        }

        const candidates = Array.from(document.querySelectorAll('header a, header button, nav a, nav button, .wt-header a, .wt-header button'));
        return candidates.find(function (element) {
            const text = textOf(element);
            return text === 'language' || text.startsWith('language ');
        }) || null;
    }

    function placeGlobalSelector() {
        if (!enableGlobalSelector) {
            return false;
        }

        if (document.querySelector('[data-potts-history-selector="1"]')) {
            return true;
        }

        const selector = buildSelector();
        const language = findLanguageControl();

        if (language) {
            const host = language.closest('li, .nav-item, .dropdown, .wt-header-item') || language.parentElement;
            if (host && host.parentElement) {
                const hostTag = host.tagName.toLowerCase();
                if (hostTag === 'li') {
                    const li = document.createElement('li');
                    li.className = host.className;
                    li.appendChild(selector);
                    host.insertAdjacentElement('afterend', li);
                } else {
                    host.insertAdjacentElement('afterend', selector);
                }
                return true;
            }
        }

        const utilityNav = document.querySelector('.wt-header .navbar-nav, header .navbar-nav, header nav, .wt-header');
        if (utilityNav) {
            utilityNav.appendChild(selector);
            return true;
        }

        selector.classList.add('potts-history-global--fallback');
        document.body.appendChild(selector);
        return true;
    }

    function synchroniseHomepageSelector() {
        document.querySelectorAll('select[name="history_region"]').forEach(function (select) {
            select.value = selected;
        });
    }

    function start() {
        formatHistoryAgeLabels(document.body);
        if (enableGlobalSelector) placeGlobalSelector();
        synchroniseHomepageSelector();

        document.addEventListener('click', function (event) {
            document.querySelectorAll('.potts-history-global.is-open').forEach(function (item) {
                if (!item.contains(event.target)) {
                    item.classList.remove('is-open');
                    const button = item.querySelector('.potts-history-global__button');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.potts-history-global.is-open').forEach(function (item) {
                    item.classList.remove('is-open');
                    const button = item.querySelector('.potts-history-global__button');
                    if (button) button.setAttribute('aria-expanded', 'false');
                });
            }
        });

        const observer = new MutationObserver(function (mutations) {
            let needsPlacement = enableGlobalSelector && !document.querySelector('[data-potts-history-selector="1"]');
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        replaceTextNode(node);
                    }
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        formatHistoryAgeLabels(node);
                    }
                });
            });
            if (needsPlacement) {
                placeGlobalSelector();
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
</script>
HTML;

        return strtr($html, [
            '__POTTS_OPTIONS__'  => $options_json ?: '[]',
            '__POTTS_SELECTED__' => $selected_json ?: '"auto"',
            '__POTTS_COOKIE__'   => $cookie_json ?: '"potts_history_region"',
            '__POTTS_SHOW_GLOBAL__' => $show_global_json,
        ]);
    }



    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
        return $this->regionSelectorBlockHtml($block_id);
    }

    public function loadAjax(): bool
    {
        return false;
    }

    public function isUserBlock(): bool
    {
        // The selector is intended only for the tree homepage.
        return false;
    }

    public function isTreeBlock(): bool
    {
        return true;
    }

    public function editBlockConfiguration(Tree $tree, int $block_id): string
    {
        return '';
    }

    public function saveBlockConfiguration(ServerRequestInterface $request, int $block_id): void
    {
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    public function dataFolder(): string
    {
        return $this->resourcesFolder() . 'data' . DIRECTORY_SEPARATOR;
    }


    private function regionSelectorBlockHtml(int $block_id): string
    {
        $regions = $this->availableHistoryRegions();
        $selected = $this->selectedHistoryRegionForDisplay();
        $select_id = 'potts-history-region-' . $block_id;
        $form_id = 'potts-history-region-form-' . $block_id;

        $options = '<option value="auto"' . ($selected === 'auto' ? ' selected' : '') . '>Site default</option>';

        foreach ($regions as $code => $label) {
            $options .= '<option value="' . $this->escape($code) . '"' . ($selected === $code ? ' selected' : '') . '>' . $this->escape($label) . '</option>';
        }

        $selected_label = $selected === 'auto'
            ? 'Site default (' . $this->defaultHistoryRegionLabel($regions) . ')'
            : ($regions[$selected] ?? $selected);

        $cookie_name = json_encode(self::REGION_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $form_id_json = json_encode($form_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return '<div class="card wt-block potts-historical-facts-selector mb-4">'
            . '<div class="card-header"><h2 class="card-title h4 mb-0">Historical facts region</h2></div>'
            . '<div class="card-body">'
            . '<form id="' . $this->escape($form_id) . '" class="row g-2 align-items-end">'
            . '<div class="col-12 col-md">'
            . '<label class="form-label" for="' . $this->escape($select_id) . '">Show historical facts for</label>'
            . '<select class="form-select" id="' . $this->escape($select_id) . '" name="history_region">' . $options . '</select>'
            . '</div>'
            . '<div class="col-12 col-md-auto">'
            . '<button class="btn btn-primary" type="submit">Apply</button>'
            . '</div>'
            . '</form>'
            . '<p class="small text-muted mb-0 mt-2">Current selection: ' . $this->escape($selected_label) . '</p>'
            . '<p class="small text-muted mb-0 mt-1">This setting is independent of the website language.</p>'
            . '</div>'
            . '</div>'
            . '<script>(function(){'
            . 'const form=document.getElementById(' . $form_id_json . ');'
            . 'if(!form){return;}'
            . 'form.addEventListener("submit",function(event){'
            . 'event.preventDefault();'
            . 'const select=form.querySelector("select[name=history_region]");'
            . 'if(!select){return;}'
            . 'const secure=window.location.protocol==="https:"?"; Secure":"";'
            . 'if(select.value==="auto"){'
            . 'document.cookie=' . $cookie_name . '+"=; Max-Age=0; Path=/; SameSite=Lax"+secure;'
            . '}else{'
            . 'document.cookie=' . $cookie_name . '+"="+encodeURIComponent(select.value)+"; Max-Age=31536000; Path=/; SameSite=Lax"+secure;'
            . '}'
            . 'window.location.reload();'
            . '});'
            . '})();</script>';
    }

    private function defaultHistoryRegionLabel(array $regions): string
    {
        $default = $this->defaultHistoryRegion();

        if ($default === null) {
            return 'Australia';
        }

        return $regions[$default] ?? $default;
    }

    private function availableHistoryRegions(): array
    {
        $labels = [
            'en_AU'  => 'Australia',
            'en_NZ'  => 'New Zealand',
            'en_ENG' => 'England',
            'en_SCT' => 'Scotland',
            'en_WLS' => 'Wales',
            'en_GB'  => 'Great Britain / United Kingdom',
            'en_IE'  => 'Ireland',
            'en_US'  => 'United States',
            'en_CA'  => 'Canada',
            'en_ZA'  => 'South Africa',
            'en_DE'  => 'Germany',
            'en_NL'  => 'Netherlands',
            'nl_NL'  => 'Netherlands - Dutch',
            'nl'     => 'Netherlands - Dutch',
            'en_FR'  => 'France',
            'en_IT'  => 'Italy',
            'en_CN'  => 'China',
            'en_IN'  => 'India',
            'en_GR'  => 'Greece',
            'en_MT'  => 'Malta',
        ];

        $regions = [];
        $files = glob($this->dataFolder() . '*.csv') ?: [];

        foreach ($files as $file) {
            $code = $this->normaliseHistoryCode(pathinfo($file, PATHINFO_FILENAME));

            if ($this->isValidHistoryCode($code)) {
                $regions[$code] = $labels[$code] ?? $code;
            }
        }

        uasort($regions, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return $regions;
    }

    private function selectedHistoryRegionForDisplay(): string
    {
        $region = $_COOKIE[self::REGION_COOKIE] ?? '';
        $region = $this->normaliseHistoryCode((string) $region);

        if ($region === '' || $region === 'auto' || !$this->isValidHistoryCode($region) || $this->csvPathForHistoryCode($region) === null) {
            return 'auto';
        }

        return $region;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function historicEventsForIndividual(Individual $individual): Collection
    {
        return $this->buildHistoricalEventGedcom(I18N::languageTag(), $individual)
            ->map(function (string $gedcom) use ($individual): Fact {
                return $this->makeFact($gedcom, $individual);
            });
    }

    public function historicEventsAll(string $language_tag): Collection
    {
        $individual = $this->currentPageIndividual();

        if (!$individual instanceof Individual) {
            return new Collection();
        }

        return $this->buildHistoricalEventGedcom($language_tag, $individual);
    }

    private function makeFact(string $gedcom, Individual $individual): Fact
    {
        // webtrees uses "histo" to identify generated historical facts.
        // This is what lets the Historic events checkbox hide/show these rows.
        $id = 'histo';

        try {
            $reflection = new \ReflectionClass(Fact::class);
            $constructor = $reflection->getConstructor();

            if ($constructor !== null && $constructor->getNumberOfParameters() >= 3) {
                return new Fact($gedcom, $individual, $id);
            }
        } catch (\Throwable $exception) {
            // Fall through to older constructor.
        }

        return new Fact($gedcom, $individual);
    }

    private function buildHistoricalEventGedcom(string $language_tag, ?Individual $individual): Collection
    {
        $events = new Collection();
        $file = $this->bestCsvFile($language_tag);

        if ($file === null) {
            return $events;
        }

        $history_language = $this->languageCodeFromFile($file);
        $rows = $this->loadCsvFile($file);

        $birth = $individual instanceof Individual ? $this->personBirthDate($individual) : null;
        $death = $individual instanceof Individual ? $this->personDeathDate($individual) : null;
        $life_end = $death['date'] ?? null;

        if ($birth !== null && $life_end === null) {
            $maximum = $birth['date']->modify('+' . $this->maximumLifespan() . ' years');
            $today = new \DateTimeImmutable('today');
            $life_end = $maximum < $today ? $maximum : $today;
        }

        // If we are on an individual page but cannot find a birth date,
        // do not show the whole CSV. This prevents the "all events" problem.
        if ($individual instanceof Individual && $birth === null) {
            return $events;
        }

        foreach ($rows as $row) {
            $date = trim((string) ($row['date'] ?? ''));
            $end_date = trim((string) ($row['end_date'] ?? ''));
            $event_text = $this->cleanGedcomText((string) ($row['event_text'] ?? ''));
            $link = $this->safeSourceLink((string) ($row['link'] ?? ''));
            $category = $this->cleanGedcomText((string) ($row['category'] ?? ''));

            if ($date === '' || $event_text === '') {
                continue;
            }

            $start_date = $this->parseHistoricalDate($date);

            // This module deliberately only supports four-digit CE years.
            // Rows with BCE, one, two or three digit years are ignored rather than
            // being displayed without lifespan filtering.
            if ($start_date === null) {
                continue;
            }

            $finish_date = $end_date !== '' ? $this->parseHistoricalDate($end_date) : null;

            if ($end_date !== '' && $finish_date === null) {
                continue;
            }

            if ($individual instanceof Individual && $birth !== null) {
                // A range that began before the person was born still matters
                // when it continued into their lifetime.
                if ($finish_date !== null && $finish_date['date'] < $birth['date']) {
                    continue;
                }

                if ($finish_date === null && $start_date['date'] < $birth['date']) {
                    continue;
                }

                if ($life_end !== null && $start_date['date'] > $life_end) {
                    continue;
                }
            }

            $date_text = $date;

            if ($end_date !== '') {
                $date_text = 'FROM ' . $date . ' TO ' . $end_date;
            }

            $event_type = $category !== '' ? $category : I18N::translate('Historical facts');

            // Add a plain marker and age text to the label.
            // The module's bodyContent script then formats it into a second line.
            if ($this->showEventAges() && $birth !== null && $start_date !== null) {
                $age_text = $this->ageDisplayText($birth, $start_date, $finish_date, $history_language);

                if ($age_text !== null) {
                    $event_type .= ' ' . self::AGE_MARKER . ' ' .
                        $this->ageHeading($history_language) . ': ' .
                        $this->cleanGedcomText($age_text);
                }
            }

            $gedcom = "1 EVEN " . $event_text .
                "\n2 TYPE " . $event_type .
                "\n2 DATE " . $date_text;

            if ($link !== '') {
                $gedcom .= "\n2 NOTE [" . $this->sourceHeading($history_language) . "](" . $link . ") ";
            }

            $events->push($gedcom);
        }

        return $events;
    }

    private function bestCsvFile(string $language_tag): ?string
    {
        // The website language must never change the selected historical region.
        // The visitor's cookie overrides the persistent site default.
        $selected_region = $this->historyRegionOverride() ?? $this->defaultHistoryRegion();

        if ($selected_region !== null) {
            $path = $this->csvPathForHistoryCode($selected_region);

            if ($path !== null) {
                return $path;
            }
        }

        // Safe fallbacks if the configured default file has been removed.
        foreach (['en_AU', 'en'] as $fallback) {
            $path = $this->csvPathForHistoryCode($fallback);

            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    private function historyRegionOverride(): ?string
    {
        $region = $_COOKIE[self::REGION_COOKIE] ?? '';
        $region = $this->normaliseHistoryCode((string) $region);

        if ($region === '' || $region === 'auto') {
            return null;
        }

        if (!$this->isValidHistoryCode($region)) {
            return null;
        }

        if ($this->csvPathForHistoryCode($region) === null) {
            return null;
        }

        return $region;
    }

    private function defaultHistoryRegion(): ?string
    {
        $region = $this->configuredDefaultHistoryRegion();

        return $this->csvPathForHistoryCode($region) !== null ? $region : null;
    }

    private function configuredDefaultHistoryRegion(): string
    {
        $preference = $this->normaliseHistoryCode($this->getPreference('DEFAULT_REGION', ''));

        if ($this->csvPathForHistoryCode($preference) !== null) {
            return $preference;
        }

        // Migrate the file-based setting used by releases before 1.1.0.
        $path = $this->dataFolder() . 'default_region.txt';

        if (is_file($path)) {
            $region = trim((string) file_get_contents($path));
            $region = $this->normaliseHistoryCode($region);

            if ($region !== 'auto' && $this->isValidHistoryCode($region) && $this->csvPathForHistoryCode($region) !== null) {
                return $region;
            }
        }

        return $this->csvPathForHistoryCode(self::DEFAULTS['DEFAULT_REGION']) !== null
            ? self::DEFAULTS['DEFAULT_REGION']
            : '';
    }

    private function normaliseHistoryCode(string $code): string
    {
        $code = trim($code);
        $code = preg_replace('/\.csv$/i', '', $code) ?? $code;
        $code = str_replace('-', '_', $code);

        if ($code === '') {
            return '';
        }

        if (strtolower($code) === 'auto') {
            return 'auto';
        }

        // Legacy releases shipped both nl.csv and nl_NL.csv with identical
        // content. Preserve old cookies while exposing one canonical option.
        if (strtolower($code) === 'nl') {
            return 'nl_NL';
        }

        $parts = explode('_', $code);

        if (count($parts) >= 2) {
            return strtolower($parts[0]) . '_' . strtoupper($parts[1]);
        }

        return strtolower($parts[0]);
    }

    private function isValidHistoryCode(string $code): bool
    {
        return preg_match('/^[a-z]{2}(?:_[A-Z]{2,3})?$/', $code) === 1;
    }

    private function csvPathForHistoryCode(string $code): ?string
    {
        $code = $this->normaliseHistoryCode($code);

        if ($code === '' || $code === 'auto' || !$this->isValidHistoryCode($code)) {
            return null;
        }

        $path = $this->dataFolder() . $code . '.csv';

        return is_file($path) ? $path : null;
    }

    private function languageCodeFromFile(string $file): string
    {
        return $this->normaliseHistoryCode(pathinfo($file, PATHINFO_FILENAME));
    }

    private function loadCsvFile(string $file): array
    {
        $rows = [];
        $handle = fopen($file, 'rb');

        if ($handle === false) {
            return $rows;
        }

        try {
            while (($columns = fgetcsv($handle, 0, ';', '"', '')) !== false) {
                if ($columns === [null] || $columns === false) {
                    continue;
                }

                $columns = array_map(static function ($value): string {
                    return trim((string) $value);
                }, $columns);

                $first = $columns[0] ?? '';

                if ($first === '') {
                    continue;
                }

                // Header line: #date;end_date;event_text;link;category
                if (strtolower($first) === '#date' || strtolower($first) === 'date') {
                    continue;
                }

                // Other comment lines.
                if (str_starts_with($first, '#')) {
                    continue;
                }

                $rows[] = [
                    'date'       => $columns[0] ?? '',
                    'end_date'   => $columns[1] ?? '',
                    'event_text' => $columns[2] ?? '',
                    'link'       => $columns[3] ?? '',
                    'category'   => $columns[4] ?? '',
                ];
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function cleanGedcomText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[\r\n\t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function safeSourceLink(string $link): string
    {
        $link = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', $link) ?? $link);
        $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true) || filter_var($link, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return $link;
    }

    private function personBirthDate(Individual $individual): ?array
    {
        $gedcom = $this->individualGedcom($individual);

        if ($gedcom !== '') {
            foreach (['BIRT', 'CHR', 'BAPM'] as $tag) {
                $date = $this->dateFromGedcomEvent($gedcom, $tag);

                if ($date !== null) {
                    return $date;
                }
            }
        }

        return $this->dateObjectToParsedDate($this->safeDateObject($individual, 'getBirthDate'), false);
    }

    private function personDeathDate(Individual $individual): ?array
    {
        $gedcom = $this->individualGedcom($individual);

        if ($gedcom !== '') {
            foreach (['DEAT', 'BURI', 'CREM'] as $tag) {
                $date = $this->dateFromGedcomEvent($gedcom, $tag);

                if ($date !== null) {
                    return $date;
                }
            }
        }

        return $this->dateObjectToParsedDate($this->safeDateObject($individual, 'getDeathDate'), true);
    }

    private function individualGedcom(Individual $individual): string
    {
        try {
            if (method_exists($individual, 'gedcom')) {
                return (string) $individual->gedcom();
            }
        } catch (\Throwable $exception) {
            return '';
        }

        return '';
    }

    private function dateFromGedcomEvent(string $gedcom, string $tag): ?array
    {
        $pattern = '/\n1 ' . preg_quote($tag, '/') . '(?:(?!\n1 )[\\s\\S])*?\n2 DATE ([^\n\r]+)/';

        if (preg_match($pattern, $gedcom, $match) === 1) {
            return $this->parseHistoricalDate(trim($match[1]));
        }

        return null;
    }

    private function safeDateObject(Individual $individual, string $method)
    {
        try {
            if (method_exists($individual, $method)) {
                return $individual->{$method}();
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    private function dateObjectToParsedDate($date, bool $use_maximum): ?array
    {
        if ($date === null) {
            return null;
        }

        try {
            if (method_exists($date, 'isOK') && !$date->isOK()) {
                return null;
            }

            if (method_exists($date, 'display')) {
                $display = html_entity_decode(strip_tags((string) $date->display()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed = $this->parseHistoricalDate($display);

                if ($parsed !== null) {
                    return $parsed;
                }
            }

            $calendar_date = null;

            if ($use_maximum && method_exists($date, 'maximumDate')) {
                $calendar_date = $date->maximumDate();
            } elseif (!$use_maximum && method_exists($date, 'minimumDate')) {
                $calendar_date = $date->minimumDate();
            }

            if ($calendar_date !== null && method_exists($calendar_date, 'format')) {
                $year = (int) $calendar_date->format('%Y');
                $month = (int) $calendar_date->format('%m');
                $day = (int) $calendar_date->format('%d');

                if ($year > 0) {
                    $month = $month > 0 ? $month : 1;
                    $day = $day > 0 ? $day : 1;

                    return [
                        'date'      => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day)),
                        'year'      => $year,
                        'month'     => $month,
                        'day'       => $day,
                        'precision' => 'day',
                        'approx'    => false,
                    ];
                }
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    private function parseHistoricalDate(string $date): ?array
    {
        $original = trim($date);

        if ($original === '') {
            return null;
        }

        $text = strtoupper($original);
        $text = str_replace(['@#DGREGORIAN@', '@#DJULIAN@', ',', '.'], '', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        $approx = false;

        if (preg_match('/^(ABT|ABOUT|EST|ESTIMATED|CAL|CALCULATED)\s+(.+)$/', $text, $match) === 1) {
            $approx = true;
            $text = trim($match[2]);
        }

        if (preg_match('/^(BEF|BEFORE|AFT|AFTER)\s+(.+)$/', $text, $match) === 1) {
            $approx = true;
            $text = trim($match[2]);
        }

        if (preg_match('/^FROM\s+(.+?)\s+TO\s+(.+)$/', $text, $match) === 1) {
            $approx = true;
            $text = trim($match[1]);
        }

        if (preg_match('/^BET\s+(.+?)\s+AND\s+(.+)$/', $text, $match) === 1) {
            $approx = true;
            $text = trim($match[1]);
        }

        $months = [
            'JAN' => 1, 'JANUARY' => 1,
            'FEB' => 2, 'FEBRUARY' => 2,
            'MAR' => 3, 'MARCH' => 3,
            'APR' => 4, 'APRIL' => 4,
            'MAY' => 5,
            'JUN' => 6, 'JUNE' => 6,
            'JUL' => 7, 'JULY' => 7,
            'AUG' => 8, 'AUGUST' => 8,
            'SEP' => 9, 'SEPT' => 9, 'SEPTEMBER' => 9,
            'OCT' => 10, 'OCTOBER' => 10,
            'NOV' => 11, 'NOVEMBER' => 11,
            'DEC' => 12, 'DECEMBER' => 12,
        ];

        // 14 FEB 1966
        if (preg_match('/^(\d{1,2})\s+([A-Z]+)\s+(\d{4})$/', $text, $match) === 1) {
            $day = (int) $match[1];
            $month = $months[$match[2]] ?? null;
            $year = (int) $match[3];

            if ($month !== null && checkdate($month, $day, $year)) {
                return [
                    'date'      => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day)),
                    'year'      => $year,
                    'month'     => $month,
                    'day'       => $day,
                    'precision' => 'day',
                    'approx'    => $approx,
                ];
            }
        }

        // FEB 1966
        if (preg_match('/^([A-Z]+)\s+(\d{4})$/', $text, $match) === 1) {
            $month = $months[$match[1]] ?? null;
            $year = (int) $match[2];

            if ($month !== null && checkdate($month, 1, $year)) {
                return [
                    'date'      => new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)),
                    'year'      => $year,
                    'month'     => $month,
                    'day'       => 1,
                    'precision' => 'month',
                    'approx'    => true,
                ];
            }
        }

        // 1966
        if (preg_match('/^(\d{4})$/', $text, $match) === 1) {
            $year = (int) $match[1];

            return [
                'date'      => new \DateTimeImmutable(sprintf('%04d-01-01', $year)),
                'year'      => $year,
                'month'     => 1,
                'day'       => 1,
                'precision' => 'year',
                'approx'    => true,
            ];
        }

        return null;
    }

    private function ageHeading(string $history_language): string
    {
        return substr($history_language, 0, 2) === 'nl' ? 'Leeftijd' : 'Age';
    }

    private function sourceHeading(string $history_language): string
    {
        return substr($history_language, 0, 2) === 'nl' ? 'Bron' : 'Source';
    }

    private function ageDisplayText(array $birth, array $start_date, ?array $finish_date, string $history_language): ?string
    {
        if ($start_date['date'] < $birth['date']) {
            return null;
        }

        $language = substr($history_language, 0, 2);

        // Year-only or approximate dates should display as "about X years".
        if ($start_date['precision'] === 'year' || $birth['precision'] === 'year' || $start_date['approx'] || $birth['approx']) {
            $years = max(0, (int) $start_date['year'] - (int) $birth['year']);

            return $this->formatAgeForDisplay($years, 0, 0, true, $language);
        }

        $diff = $birth['date']->diff($start_date['date']);

        return $this->formatAgeForDisplay((int) $diff->y, (int) $diff->m, (int) $diff->d, false, $language);
    }

    private function formatAgeForDisplay(int $years, int $months, int $days, bool $about, string $language): string
    {
        if ($language === 'nl') {
            if ($years > 0) {
                return ($about ? 'ongeveer ' : '') . $years . ' jaar';
            }

            if ($months > 0) {
                return ($about ? 'ongeveer ' : '') . $months . ' ' . ($months === 1 ? 'maand' : 'maanden');
            }

            return ($about ? 'ongeveer ' : '') . $days . ' ' . ($days === 1 ? 'dag' : 'dagen');
        }

        if ($years > 0) {
            return ($about ? 'about ' : '') . $years . ' ' . ($years === 1 ? 'year' : 'years');
        }

        if ($months > 0) {
            return ($about ? 'about ' : '') . $months . ' ' . ($months === 1 ? 'month' : 'months');
        }

        return ($about ? 'about ' : '') . $days . ' ' . ($days === 1 ? 'day' : 'days');
    }

    private function currentPageIndividual(): ?Individual
    {
        $xref = $_GET['xref'] ?? $_GET['pid'] ?? null;
        $tree_name = $_GET['tree'] ?? $_GET['ged'] ?? null;

        $route = isset($_GET['route']) ? (string) $_GET['route'] : '';

        if (($xref === null || $tree_name === null) && $route !== '') {
            $parts = explode('/', trim($route, '/'));

            foreach ($parts as $index => $part) {
                if ($part === 'tree' && isset($parts[$index + 1]) && $tree_name === null) {
                    $tree_name = $parts[$index + 1];
                }

                if ($part === 'individual' && isset($parts[$index + 1]) && $xref === null) {
                    $xref = $parts[$index + 1];
                }
            }
        }

        if (!is_string($xref) || $xref === '' || !is_string($tree_name) || $tree_name === '') {
            return null;
        }

        try {
            if (class_exists(Registry::class)
                && method_exists(Registry::class, 'treeFactory')
                && method_exists(Registry::class, 'individualFactory')) {
                $tree = Registry::treeFactory()->make($tree_name);

                if ($tree !== null) {
                    $individual = Registry::individualFactory()->make($xref, $tree);

                    if ($individual instanceof Individual) {
                        return $individual;
                    }
                }
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }
};
