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

    private const CUSTOM_VERSION = '1.1.0';
    private const LATEST_VERSION_URL = 'https://raw.githubusercontent.com/PottsNet/potts-historical-facts/main/latest-version.txt';

    private const REGION_COOKIE = 'potts_history_region';
    private const COLLECTIONS_COOKIE = 'potts_history_collections';
    private const AGE_MARKER = '__POTTS_HISTORY_AGE__';
    private const DEFAULTS = [
        'DEFAULT_REGION'        => 'en_AU',
        'DEFAULT_COLLECTIONS'   => 'en_AU',
        'ENABLED_COLLECTIONS'   => '',
        'SHOW_GLOBAL_SELECTOR'  => '1',
        'SHOW_EVENT_AGES'       => '1',
        'MAX_LIFESPAN'          => '120',
    ];

    public function title(): string
    {
        return I18N::translate('Potts Historical Facts');
    }

    public function description(): string
    {
        return I18N::translate('Displays historical facts from CSV files using visitor-selected historical fact collections available from every page.');
    }

    public function customModuleAuthorName(): string
    {
        return 'Jason Potts';
    }

    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    public function customModuleLatestVersion(): string
    {
        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {
                $latest = trim((string) @file_get_contents(self::LATEST_VERSION_URL));

                if (preg_match('/^v?(\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?)$/', $latest, $match) === 1) {
                    return $match[1];
                }

                return $this->customModuleVersion();
            },
            86400
        );
    }

    public function customModuleLatestVersionUrl(): string
    {
        return self::LATEST_VERSION_URL;
    }

    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/PottsNet/potts-historical-facts';
    }

    public function customTranslations(string $language): array
    {
        $translations = [
            'nl' => [
                'Potts Historical Facts' => 'Potts historische feiten',
                'Displays historical facts from CSV files using visitor-selected historical fact collections available from every page.' => 'Toont historische feiten uit CSV-bestanden met door bezoekers gekozen historische feitencollecties die vanaf elke pagina beschikbaar zijn.',
                'Potts Historical Facts settings' => 'Instellingen voor Potts historische feiten',
                'The Potts Historical Facts settings have been saved.' => 'De instellingen voor Potts historische feiten zijn opgeslagen.',
                'The default settings have been restored.' => 'De standaardinstellingen zijn hersteld.',
                'These preferences are stored by webtrees and are retained when the module is upgraded.' => 'Deze voorkeuren worden door webtrees opgeslagen en blijven behouden wanneer de module wordt bijgewerkt.',
                'Collections and event display' => 'Collecties en weergave van gebeurtenissen',
                'Available historical fact collections' => 'Beschikbare historische feitencollecties',
                'Only ticked collections are offered to visitors in the header selector and homepage block.' => 'Alleen aangevinkte collecties worden aan bezoekers aangeboden in de kopselector en het blok op de startpagina.',
                'Site default collections' => 'Standaardcollecties van de site',
                'Used until a visitor makes their own selection. You can choose more than one.' => 'Wordt gebruikt totdat een bezoeker een eigen keuze maakt. U kunt er meer dan één kiezen.',
                'Maximum assumed lifespan' => 'Maximaal aangenomen levensduur',
                'Limits events when no death date is recorded.' => 'Beperkt gebeurtenissen wanneer er geen overlijdensdatum is vastgelegd.',
                'Optional features' => 'Optionele functies',
                'Show the History selector in the site header' => 'Toon de geschiedeniskiezer in de sitekop',
                'The homepage block remains available when this is turned off.' => 'Het blok op de startpagina blijft beschikbaar wanneer dit is uitgeschakeld.',
                'Show ages on historical events' => 'Toon leeftijden bij historische gebeurtenissen',
                'Turn this off if another module already supplies historical-event ages.' => 'Schakel dit uit als een andere module al leeftijden bij historische gebeurtenissen toont.',
                'Custom CSV files' => 'Aangepaste CSV-bestanden',
                'To add your own historical fact collections, place CSV files in this persistent webtrees data folder. Files in this folder are not replaced when the module is upgraded.' => 'Plaats CSV-bestanden in deze blijvende webtrees-gegevensmap om uw eigen historische feitencollecties toe te voegen. Bestanden in deze map worden niet vervangen wanneer de module wordt bijgewerkt.',
                'CSV format:' => 'CSV-formaat:',
                'No persistent data folder was detected on this installation.' => 'Er is geen blijvende gegevensmap gevonden in deze installatie.',
                'Restore defaults' => 'Standaardinstellingen herstellen',
                'Save settings' => 'Instellingen opslaan',
                'Restore the Potts Historical Facts defaults?' => 'De standaardinstellingen voor Potts historische feiten herstellen?',
                'Historical fact collections' => 'Historische feitencollecties',
                'Show historical facts from' => 'Toon historische feiten uit',
                'Current selection: %s' => 'Huidige selectie: %s',
                'This setting is independent of the website language. Where matching language-specific CSV files exist, the module will use the CSV that best matches the visitor\'s selected language.' => 'Deze instelling is onafhankelijk van de websitetaal. Wanneer overeenkomende taalspecifieke CSV-bestanden bestaan, gebruikt de module het CSV-bestand dat het beste past bij de gekozen taal van de bezoeker.',
                'Apply' => 'Toepassen',
                'Site default' => 'Sitestandaard',
                'Site default (%s)' => 'Sitestandaard (%s)',
                'Choose one or more historical fact collections.' => 'Kies een of meer historische feitencollecties.',
                'History' => 'Geschiedenis',
                'Historical facts' => 'Historische feiten',
                'Age' => 'Leeftijd',
                'Source' => 'Bron',
            ],
            'de' => [
                'Potts Historical Facts' => 'Potts Historische Fakten',
                'Potts Historical Facts settings' => 'Einstellungen für Potts Historische Fakten',
                'Collections and event display' => 'Sammlungen und Ereignisanzeige',
                'Available historical fact collections' => 'Verfügbare Sammlungen historischer Fakten',
                'Site default collections' => 'Standardsammlungen der Website',
                'Optional features' => 'Optionale Funktionen',
                'Custom CSV files' => 'Eigene CSV-Dateien',
                'Restore defaults' => 'Standardeinstellungen wiederherstellen',
                'Save settings' => 'Einstellungen speichern',
                'Historical fact collections' => 'Sammlungen historischer Fakten',
                'Show historical facts from' => 'Historische Fakten anzeigen aus',
                'Apply' => 'Anwenden',
                'Site default' => 'Website-Standard',
                'Site default (%s)' => 'Website-Standard (%s)',
                'Choose one or more historical fact collections.' => 'Wählen Sie eine oder mehrere Sammlungen historischer Fakten aus.',
                'History' => 'Geschichte',
                'Historical facts' => 'Historische Fakten',
                'Age' => 'Alter',
                'Source' => 'Quelle',
            ],
            'fr' => [
                'Potts Historical Facts' => 'Faits historiques Potts',
                'Potts Historical Facts settings' => 'Paramètres des faits historiques Potts',
                'Collections and event display' => 'Collections et affichage des événements',
                'Available historical fact collections' => 'Collections de faits historiques disponibles',
                'Site default collections' => 'Collections par défaut du site',
                'Optional features' => 'Fonctions optionnelles',
                'Custom CSV files' => 'Fichiers CSV personnalisés',
                'Restore defaults' => 'Restaurer les valeurs par défaut',
                'Save settings' => 'Enregistrer les paramètres',
                'Historical fact collections' => 'Collections de faits historiques',
                'Show historical facts from' => 'Afficher les faits historiques depuis',
                'Apply' => 'Appliquer',
                'Site default' => 'Valeur par défaut du site',
                'Site default (%s)' => 'Valeur par défaut du site (%s)',
                'Choose one or more historical fact collections.' => 'Choisissez une ou plusieurs collections de faits historiques.',
                'History' => 'Histoire',
                'Historical facts' => 'Faits historiques',
                'Age' => 'Âge',
                'Source' => 'Source',
            ],
            'pl' => [
                'Potts Historical Facts' => 'Fakty historyczne Potts',
                'Potts Historical Facts settings' => 'Ustawienia faktów historycznych Potts',
                'Collections and event display' => 'Kolekcje i wyświetlanie wydarzeń',
                'Available historical fact collections' => 'Dostępne kolekcje faktów historycznych',
                'Site default collections' => 'Domyślne kolekcje witryny',
                'Optional features' => 'Funkcje opcjonalne',
                'Custom CSV files' => 'Własne pliki CSV',
                'Restore defaults' => 'Przywróć domyślne',
                'Save settings' => 'Zapisz ustawienia',
                'Historical fact collections' => 'Kolekcje faktów historycznych',
                'Show historical facts from' => 'Pokaż fakty historyczne z',
                'Apply' => 'Zastosuj',
                'Site default' => 'Domyślne witryny',
                'Site default (%s)' => 'Domyślne witryny (%s)',
                'Choose one or more historical fact collections.' => 'Wybierz jedną lub więcej kolekcji faktów historycznych.',
                'History' => 'Historia',
                'Historical facts' => 'Fakty historyczne',
                'Age' => 'Wiek',
                'Source' => 'Źródło',
            ],
            'pt' => [
                'Potts Historical Facts' => 'Factos Históricos Potts',
                'Potts Historical Facts settings' => 'Definições dos Factos Históricos Potts',
                'Collections and event display' => 'Colecções e apresentação de eventos',
                'Available historical fact collections' => 'Colecções de factos históricos disponíveis',
                'Site default collections' => 'Colecções predefinidas do site',
                'Optional features' => 'Funcionalidades opcionais',
                'Custom CSV files' => 'Ficheiros CSV personalizados',
                'Restore defaults' => 'Repor predefinições',
                'Save settings' => 'Guardar definições',
                'Historical fact collections' => 'Colecções de factos históricos',
                'Show historical facts from' => 'Mostrar factos históricos de',
                'Apply' => 'Aplicar',
                'Site default' => 'Predefinição do site',
                'Site default (%s)' => 'Predefinição do site (%s)',
                'Choose one or more historical fact collections.' => 'Escolha uma ou mais colecções de factos históricos.',
                'History' => 'História',
                'Historical facts' => 'Factos históricos',
                'Age' => 'Idade',
                'Source' => 'Fonte',
            ],
        ];

        $base = strtolower(strtok(str_replace('_', '-', $language), '-') ?: $language);

        return $translations[$base] ?? [];
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
        $this->ensureUserDataFolder();

        return $this->viewResponse('potts-historical-facts::admin/settings', [
            'title'      => I18N::translate('Potts Historical Facts settings'),
            'action_url' => route('module', [
                'module' => $this->name(),
                'action' => 'Admin',
            ]),
            'collections' => $this->availableHistoryCollections(),
            'settings'   => $this->settings(),
            'saved'      => Validator::queryParams($request)->boolean('saved', false),
            'reset'      => Validator::queryParams($request)->boolean('reset', false),
            'version'    => $this->customModuleVersion(),
            'user_data_folder' => $this->userDataFolder(),
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

        $available = $this->availableHistoryCollections();
        $available_codes = array_keys($available);

        $enabled_input = $data['enabled_collections'] ?? [];
        $enabled = is_array($enabled_input)
            ? $this->normaliseHistoryCodeList($enabled_input)
            : $this->normaliseHistoryCodeList((string) $enabled_input);
        $enabled = array_values(array_intersect($enabled, $available_codes));

        // Avoid saving an unusable configuration. If everything was unticked,
        // keep every bundled CSV collection available.
        if ($enabled === []) {
            $enabled = $available_codes;
        }

        $default_input = $data['default_collections'] ?? [];
        $defaults = is_array($default_input)
            ? $this->normaliseHistoryCodeList($default_input)
            : $this->normaliseHistoryCodeList((string) $default_input);
        $defaults = array_values(array_intersect($defaults, $enabled));

        // Compatibility with the old single-select setting name.
        if ($defaults === []) {
            $region = isset($data['default_region']) && is_string($data['default_region'])
                ? $this->canonicalHistorySelectionCode($this->normaliseHistoryCode($data['default_region']))
                : $this->canonicalHistorySelectionCode(self::DEFAULTS['DEFAULT_REGION']);

            if (in_array($region, $enabled, true)) {
                $defaults = [$region];
            }
        }

        if ($defaults === [] && $enabled !== []) {
            $defaults = [$enabled[0]];
        }

        $lifespan = isset($data['max_lifespan']) ? (int) $data['max_lifespan'] : (int) self::DEFAULTS['MAX_LIFESPAN'];
        $lifespan = max(80, min(150, $lifespan));

        $this->setPreference('ENABLED_COLLECTIONS', implode(',', $enabled));
        $this->setPreference('DEFAULT_COLLECTIONS', implode(',', $defaults));
        $this->setPreference('DEFAULT_REGION', $defaults[0] ?? self::DEFAULTS['DEFAULT_REGION']);
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
            'DEFAULT_COLLECTIONS'  => implode(',', $this->defaultHistoryCollections()),
            'ENABLED_COLLECTIONS'  => implode(',', $this->enabledHistoryCollectionCodes()),
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
        $collections = $this->enabledHistoryCollections();
        $selected = $this->selectedHistoryCollectionsForDisplay();
        $default_label = $this->defaultHistoryCollectionsLabel($collections);

        $options = [];

        foreach ($collections as $code => $label) {
            $options[] = ['code' => $code, 'label' => $label];
        }

        $options_json = json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $selected_json = json_encode($selected, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $default_label_json = json_encode($default_label, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $ui_strings_json = json_encode([
            'choose_collections' => I18N::translate('Choose one or more historical fact collections.'),
            'site_default'       => I18N::translate('Site default'),
            'site_default_with'  => I18N::translate('Site default (%s)'),
            'apply'              => I18N::translate('Apply'),
            'history'            => I18N::translate('History'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $collection_cookie_json = json_encode(self::COLLECTIONS_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $legacy_cookie_json = json_encode(self::REGION_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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
    width: min(24rem, calc(100vw - 2rem));
    max-height: min(32rem, 76vh);
    overflow: auto;
    display: none;
    padding: .65rem;
    margin: 0;
    background: #fffdf8;
    color: #18313b;
    border: 1px solid rgba(35, 48, 56, .18);
    border-radius: .75rem;
    box-shadow: 0 .85rem 2rem rgba(25, 42, 50, .22);
}
.potts-history-global.is-open .potts-history-global__menu {
    display: block;
}
.potts-history-global__intro {
    margin: 0 0 .55rem;
    color: #4b6470;
    font-size: .875rem;
}
.potts-history-global__option {
    display: flex;
    gap: .55rem;
    align-items: flex-start;
    width: 100%;
    border: 0;
    border-radius: .5rem;
    background: transparent;
    color: inherit;
    text-align: left;
    padding: .38rem .45rem;
    cursor: pointer;
}
.potts-history-global__option:hover,
.potts-history-global__option:focus-within {
    background: #eef3ea;
}
.potts-history-global__option input {
    margin-top: .22rem;
}
.potts-history-global__actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    justify-content: flex-end;
    margin-top: .7rem;
    padding-top: .7rem;
    border-top: 1px solid rgba(35, 48, 56, .12);
}
.potts-history-global__action {
    border: 1px solid rgba(35, 48, 56, .18);
    border-radius: 999px;
    background: #fff;
    color: #18313b;
    font: inherit;
    font-weight: 700;
    padding: .35rem .75rem;
    cursor: pointer;
}
.potts-history-global__action--primary {
    background: #185a71;
    color: #fff;
    border-color: #185a71;
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
    const defaultLabel = __POTTS_DEFAULT_LABEL__;
    const uiText = __POTTS_UI_STRINGS__;
    const collectionCookieName = __POTTS_COLLECTION_COOKIE__;
    const legacyCookieName = __POTTS_LEGACY_COOKIE__;
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

    function selectedLabels() {
        if (!selected || !Array.isArray(selected.codes) || selected.codes.length === 0 || selected.mode === 'auto') {
            return [(uiText.site_default_with || 'Site default (%s)').replace('%s', defaultLabel)];
        }

        const labels = selected.codes.map(function (code) {
            const match = options.find(function (item) { return item.code === code; });
            return match ? match.label : code;
        });

        return labels.length ? labels : [(uiText.site_default_with || 'Site default (%s)').replace('%s', defaultLabel)];
    }

    function selectedLabel() {
        const labels = selectedLabels();

        if (labels.length === 1) {
            return labels[0];
        }

        return labels[0] + ' +' + (labels.length - 1);
    }

    function saveCollections(codes) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        const validCodes = (codes || []).filter(function (code, index, array) {
            return options.some(function (item) { return item.code === code; }) && array.indexOf(code) === index;
        });

        if (validCodes.length === 0) {
            document.cookie = collectionCookieName + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
            document.cookie = legacyCookieName + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
        } else {
            document.cookie = collectionCookieName + '=' + encodeURIComponent(validCodes.join(',')) + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
            document.cookie = legacyCookieName + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
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
        button.setAttribute('aria-haspopup', 'dialog');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', 'potts-history-global-menu');
        button.textContent = historyLabel() + ': ' + selectedLabel();

        const menu = document.createElement('div');
        menu.id = 'potts-history-global-menu';
        menu.className = 'potts-history-global__menu';
        menu.setAttribute('role', 'dialog');
        menu.setAttribute('aria-label', historyLabel());

        const intro = document.createElement('p');
        intro.className = 'potts-history-global__intro';
        intro.textContent = uiText.choose_collections || 'Choose one or more historical fact collections.';
        menu.appendChild(intro);

        const selectedCodes = selected && selected.mode !== 'auto' && Array.isArray(selected.codes) ? selected.codes : [];
        const checkboxes = [];

        options.forEach(function (item) {
            const label = document.createElement('label');
            label.className = 'potts-history-global__option';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = item.code;
            checkbox.checked = selectedCodes.indexOf(item.code) !== -1;
            checkboxes.push(checkbox);

            const text = document.createElement('span');
            text.textContent = item.label;

            label.appendChild(checkbox);
            label.appendChild(text);
            menu.appendChild(label);
        });

        const actions = document.createElement('div');
        actions.className = 'potts-history-global__actions';

        const reset = document.createElement('button');
        reset.type = 'button';
        reset.className = 'potts-history-global__action';
        reset.textContent = uiText.site_default || 'Site default';
        reset.addEventListener('click', function () { saveCollections([]); });

        const apply = document.createElement('button');
        apply.type = 'button';
        apply.className = 'potts-history-global__action potts-history-global__action--primary';
        apply.textContent = uiText.apply || 'Apply';
        apply.addEventListener('click', function () {
            saveCollections(checkboxes.filter(function (checkbox) { return checkbox.checked; }).map(function (checkbox) { return checkbox.value; }));
        });

        actions.appendChild(reset);
        actions.appendChild(apply);
        menu.appendChild(actions);

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

            if (open && checkboxes.length) {
                checkboxes[0].focus();
            }
        });

        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                wrapper.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                if (checkboxes.length) checkboxes[0].focus();
            }
        });

        menu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                wrapper.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
                button.focus();
            }
        });

        wrapper.appendChild(button);
        wrapper.appendChild(menu);
        return wrapper;
    }

    function textOf(element) {
        return (element && element.textContent ? element.textContent : '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function pageLanguage() {
        return ((document.documentElement && document.documentElement.lang) || navigator.language || 'en').toLowerCase();
    }

    function historyLabel() {
        return uiText.history || 'History';
    }

    function identityOf(element) {
        if (!element) {
            return '';
        }

        return [
            element.id || '',
            element.className || '',
            element.getAttribute('aria-label') || '',
            element.getAttribute('title') || '',
            element.getAttribute('href') || '',
            element.getAttribute('data-bs-target') || '',
            element.getAttribute('data-bs-toggle') || ''
        ].join(' ').toLowerCase();
    }

    function isLanguageControl(element) {
        const text = textOf(element);
        const identity = identityOf(element);
        const words = [
            'language',
            'taal',
            'sprache',
            'langue',
            'idioma',
            'lingua',
            'język',
            'jezyk',
            'språk',
            'sprog',
            'kieli',
            'nyelv',
            'jazyk',
            'jazyky'
        ];

        if (words.some(function (word) {
            return text === word || text.startsWith(word + ' ') || text.endsWith(' ' + word);
        })) {
            return true;
        }

        return /(?:^|[\s_\-\/])(language|locale|lang)(?:[\s_\-\/]|$)/.test(identity);
    }

    function findLanguageControl() {
        const stableSelectors = [
            '.wt-language-menu',
            '[data-wt-menu="language"]',
            '#language-menu',
            '[id*="language"]',
            '[class*="language"]',
            '[id*="locale"]',
            '[class*="locale"]'
        ];

        for (const selector of stableSelectors) {
            const stable = document.querySelector(selector);
            if (stable && isLanguageControl(stable)) {
                return stable;
            }
        }

        const searchRoots = [
            '.potts-utility-nav',
            '.wt-user-menu',
            '.wt-header',
            '.wt-header-wrapper',
            'body > header',
            'header[role="banner"]',
            'header'
        ];

        for (const rootSelector of searchRoots) {
            const root = document.querySelector(rootSelector);
            if (!root) {
                continue;
            }

            const candidates = Array.from(root.querySelectorAll('a, button, summary, [role="button"], .dropdown-toggle'));
            const match = candidates.find(isLanguageControl);
            if (match) {
                return match;
            }
        }

        return null;
    }

    function findUtilityNavigation() {
        const selectors = [
            '.potts-utility-menu',
            '.potts-utility-nav .navbar-nav',
            '.potts-utility-nav',
            'header .wt-user-menu',
            '.wt-header .wt-user-menu',
            '.wt-header-wrapper .wt-user-menu'
        ];

        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (element) {
                return element;
            }
        }

        return null;
    }

    function prepareHeaderSelector(selector) {
        selector.classList.add('potts-history-global--in-header');
        const button = selector.querySelector('.potts-history-global__button');

        if (button) {
            button.classList.add('nav-link');
        }
    }

    function insertSelectorAfterHost(selector, host) {
        if (!host || !host.parentElement) {
            return false;
        }

        prepareHeaderSelector(selector);

        if (host.tagName.toLowerCase() === 'li' || host.classList.contains('nav-item')) {
            const li = document.createElement('li');
            li.className = 'nav-item potts-history-global-item';
            li.appendChild(selector);
            host.insertAdjacentElement('afterend', li);
            return true;
        }

        host.insertAdjacentElement('afterend', selector);
        return true;
    }

    function appendSelectorToUtilityNavigation(selector, utilityNav) {
        if (!utilityNav) {
            return false;
        }

        prepareHeaderSelector(selector);

        if (utilityNav.matches('ul, ol, .navbar-nav, .nav')) {
            const li = document.createElement('li');
            li.className = 'nav-item potts-history-global-item';
            li.appendChild(selector);
            utilityNav.appendChild(li);
            return true;
        }

        utilityNav.appendChild(selector);
        return true;
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
            if (insertSelectorAfterHost(selector, host)) {
                return true;
            }
        }

        if (appendSelectorToUtilityNavigation(selector, findUtilityNavigation())) {
            return true;
        }

        selector.classList.add('potts-history-global--fallback');
        document.body.appendChild(selector);
        return true;
    }

    function synchroniseHomepageSelector() {
        document.querySelectorAll('[data-potts-history-collection]').forEach(function (checkbox) {
            checkbox.checked = selected && selected.mode !== 'auto' && Array.isArray(selected.codes) && selected.codes.indexOf(checkbox.value) !== -1;
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
            '__POTTS_SELECTED__' => $selected_json ?: '{"mode":"auto","codes":[]}',
            '__POTTS_DEFAULT_LABEL__' => $default_label_json ?: '"Australia"',
            '__POTTS_UI_STRINGS__' => $ui_strings_json ?: '{}',
            '__POTTS_COLLECTION_COOKIE__' => $collection_cookie_json ?: '"potts_history_collections"',
            '__POTTS_LEGACY_COOKIE__' => $legacy_cookie_json ?: '"potts_history_region"',
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
        return $this->bundledDataFolder();
    }

    private function bundledDataFolder(): string
    {
        return $this->resourcesFolder() . 'data' . DIRECTORY_SEPARATOR;
    }

    private function userDataFolder(): string
    {
        if (defined('WT_DATA_DIR') && is_string(WT_DATA_DIR) && WT_DATA_DIR !== '') {
            return rtrim(WT_DATA_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->name() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        }

        return '';
    }

    private function ensureUserDataFolder(): void
    {
        $folder = $this->userDataFolder();

        if ($folder !== '' && !is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }
    }

    /** @return list<string> */
    private function historyDataFolders(): array
    {
        $folders = [];
        $user_folder = $this->userDataFolder();

        if ($user_folder !== '' && is_dir($user_folder)) {
            $folders[] = $user_folder;
        }

        $folders[] = $this->bundledDataFolder();

        return array_values(array_unique($folders));
    }


    private function regionSelectorBlockHtml(int $block_id): string
    {
        $collections = $this->enabledHistoryCollections();
        $selected = $this->selectedHistoryCollectionsForDisplay();
        $form_id = 'potts-history-collections-form-' . $block_id;
        $selected_codes = $selected['mode'] === 'custom' ? $selected['codes'] : [];

        $checkboxes = '';

        foreach ($collections as $code => $label) {
            $input_id = 'potts-history-collection-' . $block_id . '-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $code);
            $checkboxes .= '<div class="form-check">'
                . '<input class="form-check-input" type="checkbox" value="' . $this->escape($code) . '" id="' . $this->escape($input_id) . '" data-potts-history-collection="1"' . (in_array($code, $selected_codes, true) ? ' checked' : '') . '>'
                . '<label class="form-check-label" for="' . $this->escape($input_id) . '">' . $this->escape($label) . '</label>'
                . '</div>';
        }

        $selected_label = $this->selectedHistoryCollectionsLabel($selected, $collections);

        $collection_cookie = json_encode(self::COLLECTIONS_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $legacy_cookie = json_encode(self::REGION_COOKIE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $form_id_json = json_encode($form_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return '<div class="card wt-block potts-historical-facts-selector mb-4">'
            . '<div class="card-header"><h2 class="card-title h4 mb-0">' . $this->escape(I18N::translate('Historical fact collections')) . '</h2></div>'
            . '<div class="card-body">'
            . '<form id="' . $this->escape($form_id) . '" class="potts-history-collections-form">'
            . '<fieldset class="mb-3">'
            . '<legend class="form-label fs-6 mb-2">' . $this->escape(I18N::translate('Show historical facts from')) . '</legend>'
            . '<div class="row row-cols-1 row-cols-md-2 g-1">' . $checkboxes . '</div>'
            . '</fieldset>'
            . '<div class="d-flex flex-wrap gap-2">'
            . '<button class="btn btn-primary" type="submit" name="task" value="apply">' . $this->escape(I18N::translate('Apply')) . '</button>'
            . '<button class="btn btn-outline-secondary" type="submit" name="task" value="default">' . $this->escape(I18N::translate('Site default')) . '</button>'
            . '</div>'
            . '</form>'
            . '<p class="small text-muted mb-0 mt-2">' . $this->escape(I18N::translate('Current selection: %s', $selected_label)) . '</p>'
            . '<p class="small text-muted mb-0 mt-1">' . $this->escape(I18N::translate('This setting is independent of the website language. Where matching language-specific CSV files exist, the module will use the CSV that best matches the visitor\'s selected language.')) . '</p>'
            . '</div>'
            . '</div>'
            . '<script>(function(){'
            . 'const form=document.getElementById(' . $form_id_json . ');'
            . 'if(!form){return;}'
            . 'form.addEventListener("submit",function(event){'
            . 'event.preventDefault();'
            . 'const secure=window.location.protocol==="https:"?"; Secure":"";'
            . 'const task=event.submitter&&event.submitter.value?event.submitter.value:"apply";'
            . 'if(task==="default"){'
            . 'document.cookie=' . $collection_cookie . '+"=; Max-Age=0; Path=/; SameSite=Lax"+secure;'
            . 'document.cookie=' . $legacy_cookie . '+"=; Max-Age=0; Path=/; SameSite=Lax"+secure;'
            . '}else{'
            . 'const codes=Array.from(form.querySelectorAll("[data-potts-history-collection]:checked")).map(function(input){return input.value;});'
            . 'if(codes.length===0){'
            . 'document.cookie=' . $collection_cookie . '+"=; Max-Age=0; Path=/; SameSite=Lax"+secure;'
            . '}else{'
            . 'document.cookie=' . $collection_cookie . '+"="+encodeURIComponent(codes.join(","))+"; Max-Age=31536000; Path=/; SameSite=Lax"+secure;'
            . '}'
            . 'document.cookie=' . $legacy_cookie . '+"=; Max-Age=0; Path=/; SameSite=Lax"+secure;'
            . '}'
            . 'window.location.reload();'
            . '});'
            . '})();</script>';
    }

    private function defaultHistoryCollectionsLabel(array $collections): string
    {
        $defaults = $this->defaultHistoryCollections();
        $labels = [];

        foreach ($defaults as $code) {
            if (isset($collections[$code])) {
                $labels[] = $collections[$code];
            }
        }

        if ($labels === []) {
            return I18N::translate('Australia');
        }

        return implode(', ', $labels);
    }

    private function selectedHistoryCollectionsLabel(array $selected, array $collections): string
    {
        if (($selected['mode'] ?? 'auto') === 'auto') {
            return I18N::translate('Site default (%s)', $this->defaultHistoryCollectionsLabel($collections));
        }

        $labels = [];

        foreach (($selected['codes'] ?? []) as $code) {
            if (isset($collections[$code])) {
                $labels[] = $collections[$code];
            }
        }

        return $labels !== [] ? implode(', ', $labels) : I18N::translate('Site default (%s)', $this->defaultHistoryCollectionsLabel($collections));
    }

    /** @return array<string,string> */
    private function availableHistoryCollections(): array
    {
        $labels = [
            'en_AH'  => 'Austro-Hungarian Empire',
            'en_AT'  => 'Austria',
            'en_AU'  => 'Australia',
            'en_CA'  => 'Canada',
            'en_CN'  => 'China',
            'en_CZ'  => 'Czech lands',
            'en_DE'  => 'Germany',
            'en_ENG' => 'England',
            'en_EUR' => 'Europe',
            'en_FR'  => 'France',
            'en_GB'  => 'Great Britain / United Kingdom',
            'en_GR'  => 'Greece',
            'en_HU'  => 'Hungary',
            'en_IE'  => 'Ireland',
            'en_IN'  => 'India',
            'en_IT'  => 'Italy',
            'en_MT'  => 'Malta',
            'en_NL'  => 'Netherlands',
            'en_NZ'  => 'New Zealand',
            'en_PL'  => 'Poland',
            'en_SCT' => 'Scotland',
            'en_SK'  => 'Slovakia',
            'en_US'  => 'United States',
            'en_WLD' => 'World events',
            'en_WLS' => 'Wales',
            'en_ZA'  => 'South Africa',
        ];

        $collections = [];

        foreach ($this->historyDataFolders() as $folder) {
            $files = glob($folder . '*.csv') ?: [];

            foreach ($files as $file) {
                $code = $this->normaliseHistoryCode(pathinfo($file, PATHINFO_FILENAME));

                if (!$this->isValidHistoryCode($code)) {
                    continue;
                }

                $selection_code = $this->canonicalHistorySelectionCode($code);

                if ($selection_code !== '' && !isset($collections[$selection_code])) {
                    $collections[$selection_code] = $this->historyCollectionLabel($selection_code, $labels);
                }
            }
        }

        uasort($collections, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return $collections;
    }

    /** @param array<string,string> $labels */
    private function historyCollectionLabel(string $code, array $labels): string
    {
        if (isset($labels[$code])) {
            return I18N::translate($labels[$code]);
        }

        if (preg_match('/^([a-z]{2})_([A-Z]{2,3})$/', $code, $match) === 1) {
            return strtoupper($match[2]) . ' (' . strtolower($match[1]) . ')';
        }

        return $code;
    }

    /** @return array<string,string> */
    private function enabledHistoryCollections(): array
    {
        $available = $this->availableHistoryCollections();
        $enabled_codes = $this->enabledHistoryCollectionCodes();

        if ($enabled_codes === []) {
            return $available;
        }

        return array_intersect_key($available, array_flip($enabled_codes));
    }

    /** @return list<string> */
    private function enabledHistoryCollectionCodes(): array
    {
        $available = array_keys($this->availableHistoryCollections());
        $preference = trim($this->getPreference('ENABLED_COLLECTIONS', self::DEFAULTS['ENABLED_COLLECTIONS']));

        if ($preference === '') {
            return $available;
        }

        $codes = $this->normaliseHistoryCodeList($preference);
        $codes = array_values(array_intersect($codes, $available));

        return $codes !== [] ? $codes : $available;
    }

    /** @return array{mode:string,codes:list<string>} */
    private function selectedHistoryCollectionsForDisplay(): array
    {
        $override = $this->historyCollectionsOverride();

        if ($override === null) {
            return ['mode' => 'auto', 'codes' => []];
        }

        return ['mode' => 'custom', 'codes' => $override];
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
        $files = $this->selectedCsvFiles($language_tag);

        if ($files === []) {
            return $events;
        }

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

        $prepared = [];
        $seen = [];

        foreach ($files as $file) {
            $history_language = $this->languageCodeFromFile($file);
            $rows = $this->loadCsvFile($file);

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

                $key = strtolower($date . '|' . $end_date . '|' . $event_text);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $prepared[] = [
                    'date'             => $date,
                    'end_date'         => $end_date,
                    'event_text'       => $event_text,
                    'link'             => $link,
                    'category'         => $category,
                    'start_date'       => $start_date,
                    'finish_date'      => $finish_date,
                    'history_language' => $history_language,
                ];
            }
        }

        usort($prepared, static function (array $a, array $b): int {
            $date_compare = $a['start_date']['date'] <=> $b['start_date']['date'];

            if ($date_compare !== 0) {
                return $date_compare;
            }

            return strcasecmp((string) $a['event_text'], (string) $b['event_text']);
        });

        foreach ($prepared as $row) {
            $date = $row['date'];
            $end_date = $row['end_date'];
            $event_text = $row['event_text'];
            $link = $row['link'];
            $category = $row['category'];
            $start_date = $row['start_date'];
            $finish_date = $row['finish_date'];
            $history_language = $row['history_language'];

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

    /** @return list<string> */
    private function selectedCsvFiles(string $language_tag): array
    {
        $codes = $this->historyCollectionsOverride() ?? $this->defaultHistoryCollections();
        $files = [];

        foreach ($codes as $code) {
            $path = $this->csvPathForHistoryCodeForLanguage($code, $language_tag);

            if ($path !== null) {
                $files[] = $path;
            }
        }

        if ($files !== []) {
            return $files;
        }

        // Safe fallbacks if the configured default file has been removed.
        foreach (['en_AU', 'en'] as $fallback) {
            $path = $this->csvPathForHistoryCodeForLanguage($fallback, $language_tag);

            if ($path !== null) {
                return [$path];
            }
        }

        return [];
    }

    /** @return ?list<string> */
    private function historyCollectionsOverride(): ?array
    {
        $enabled = array_keys($this->enabledHistoryCollections());

        $raw = $_COOKIE[self::COLLECTIONS_COOKIE] ?? '';
        $codes = $this->normaliseHistoryCodeList((string) $raw);
        $codes = array_values(array_intersect($codes, $enabled));

        if ($codes !== []) {
            return $codes;
        }

        // Preserve old visitor cookies created by the single-region releases.
        $legacy_region = $_COOKIE[self::REGION_COOKIE] ?? '';
        $legacy_region = $this->canonicalHistorySelectionCode($this->normaliseHistoryCode((string) $legacy_region));

        if ($legacy_region !== '' && $legacy_region !== 'auto' && in_array($legacy_region, $enabled, true) && $this->csvPathForHistoryCode($legacy_region) !== null) {
            return [$legacy_region];
        }

        return null;
    }

    /** @return list<string> */
    private function defaultHistoryCollections(): array
    {
        $enabled = array_keys($this->enabledHistoryCollections());
        $preference = trim($this->getPreference('DEFAULT_COLLECTIONS', self::DEFAULTS['DEFAULT_COLLECTIONS']));
        $codes = $this->normaliseHistoryCodeList($preference);
        $codes = array_values(array_intersect($codes, $enabled));

        if ($codes !== []) {
            return $codes;
        }

        $legacy = $this->configuredDefaultHistoryRegion();

        if ($legacy !== '' && in_array($legacy, $enabled, true)) {
            return [$legacy];
        }

        return $enabled !== [] ? [$enabled[0]] : [];
    }

    private function defaultHistoryRegion(): ?string
    {
        $collections = $this->defaultHistoryCollections();
        $region = $collections[0] ?? $this->configuredDefaultHistoryRegion();

        $region = $this->canonicalHistorySelectionCode($region);

        return $this->csvPathForHistoryCode($region) !== null ? $region : null;
    }

    private function configuredDefaultHistoryRegion(): string
    {
        $preference = $this->normaliseHistoryCode($this->getPreference('DEFAULT_REGION', ''));

        if ($this->csvPathForHistoryCode($preference) !== null) {
            return $this->canonicalHistorySelectionCode($preference);
        }

        // Migrate the file-based setting used by releases before 1.1.0.
        $path = $this->dataFolder() . 'default_region.txt';

        if (is_file($path)) {
            $region = trim((string) file_get_contents($path));
            $region = $this->normaliseHistoryCode($region);

            if ($region !== 'auto' && $this->isValidHistoryCode($region) && $this->csvPathForHistoryCode($region) !== null) {
                return $this->canonicalHistorySelectionCode($region);
            }
        }

        return $this->csvPathForHistoryCode(self::DEFAULTS['DEFAULT_REGION']) !== null
            ? $this->canonicalHistorySelectionCode(self::DEFAULTS['DEFAULT_REGION'])
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

    /** @param string|array<int,string> $codes @return list<string> */
    private function normaliseHistoryCodeList($codes): array
    {
        if (is_string($codes)) {
            $codes = preg_split('/[\s,;|]+/', $codes) ?: [];
        }

        if (!is_array($codes)) {
            return [];
        }

        $normalised = [];

        foreach ($codes as $code) {
            $code = $this->normaliseHistoryCode((string) $code);

            if ($code !== '' && $code !== 'auto' && $this->isValidHistoryCode($code) && $this->csvPathForHistoryCode($code) !== null) {
                $code = $this->canonicalHistorySelectionCode($code);

                if ($code !== '' && !in_array($code, $normalised, true)) {
                    $normalised[] = $code;
                }
            }
        }

        return $normalised;
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

        foreach ($this->historyDataFolders() as $folder) {
            $path = $folder . $code . '.csv';

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function csvPathForHistoryCodeForLanguage(string $code, string $language_tag): ?string
    {
        $preferred = $this->languagePreferredHistoryCode($code, $language_tag);

        return $this->csvPathForHistoryCode($preferred) ?? $this->csvPathForHistoryCode($code);
    }

    private function canonicalHistorySelectionCode(string $code): string
    {
        $code = $this->normaliseHistoryCode($code);

        if (preg_match('/^[a-z]{2}_([A-Z]{2,3})$/', $code, $match) !== 1) {
            return $code;
        }

        $english_code = 'en_' . $match[1];

        if ($this->csvPathForHistoryCode($english_code) !== null) {
            return $english_code;
        }

        return $code;
    }

    private function languagePreferredHistoryCode(string $code, string $language_tag): string
    {
        $code = $this->normaliseHistoryCode($code);

        if (preg_match('/^[a-z]{2}_([A-Z]{2,3})$/', $code, $match) !== 1) {
            return $code;
        }

        $region = $match[1];
        $language = strtolower(strtok(str_replace('_', '-', $language_tag), '-') ?: 'en');
        $language_code = $language . '_' . $region;

        if ($this->csvPathForHistoryCode($language_code) !== null) {
            return $language_code;
        }

        $english_code = 'en_' . $region;

        if ($this->csvPathForHistoryCode($english_code) !== null) {
            return $english_code;
        }

        return $code;
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
        return substr($history_language, 0, 2) === 'nl' ? 'Leeftijd' : I18N::translate('Age');
    }

    private function sourceHeading(string $history_language): string
    {
        return substr($history_language, 0, 2) === 'nl' ? 'Bron' : I18N::translate('Source');
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
