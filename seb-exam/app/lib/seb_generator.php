<?php



function generate_seb_xml(array $settings): string {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    
    $impl = new DOMImplementation();
    $doctype = $impl->createDocumentType(
        'plist',
        '-//Apple//DTD PLIST 1.0//EN',
        'http://www.apple.com/DTDs/PropertyList-1.0.dtd'
    );
    $dom = $impl->createDocument('', '', $doctype);
    $dom->encoding = 'UTF-8';
    $dom->formatOutput = true;

    
    $plist = $dom->createElement('plist');
    $plist->setAttribute('version', '1.0');
    $dom->appendChild($plist);

    
    $mainDict = $dom->createElement('dict');
    $plist->appendChild($mainDict);

    

    
    addPlistKey($dom, $mainDict, 'startURL', 'string', $settings['start_url'] ?? 'http://localhost');

    
    addPlistKey($dom, $mainDict, 'browserViewMode', 'integer', (int)($settings['browser_view_mode'] ?? 1));
    addPlistKey($dom, $mainDict, 'mainBrowserWindowWidth', 'string', $settings['window_width'] ?? '100%');
    addPlistKey($dom, $mainDict, 'mainBrowserWindowHeight', 'string', $settings['window_height'] ?? '100%');
    addPlistKey($dom, $mainDict, 'enableBrowserWindowToolbar', 'boolean', !empty($settings['enable_toolbar']));
    addPlistKey($dom, $mainDict, 'hideBrowserWindowToolbar', 'boolean', empty($settings['enable_toolbar']));
    addPlistKey($dom, $mainDict, 'showReloadButton', 'boolean', !empty($settings['allow_reload']));
    addPlistKey($dom, $mainDict, 'showTaskBar', 'boolean', !empty($settings['show_taskbar']));
    addPlistKey($dom, $mainDict, 'taskBarHeight', 'integer', 40);

    
    addPlistKey($dom, $mainDict, 'allowBrowsingBackForward', 'boolean', !empty($settings['allow_navigation']));
    addPlistKey($dom, $mainDict, 'newBrowserWindowByLinkPolicy', 'integer', 0);
    addPlistKey($dom, $mainDict, 'newBrowserWindowByScriptPolicy', 'integer', 0);

    
    addPlistKey($dom, $mainDict, 'allowQuit', 'boolean', !empty($settings['allow_quit']));
    if (!empty($settings['quit_password'])) {
        
        $hashedPassword = hash('sha256', $settings['quit_password']);
        addPlistKey($dom, $mainDict, 'hashedQuitPassword', 'string', $hashedPassword);
    }

    
    addPlistKey($dom, $mainDict, 'enableScreenCapture', 'boolean', !empty($settings['allow_screen_capture']));
    addPlistKey($dom, $mainDict, 'enablePrinting', 'boolean', !empty($settings['allow_printing']));
    addPlistKey($dom, $mainDict, 'enableClipboard', 'boolean', !empty($settings['allow_clipboard']));
    addPlistKey($dom, $mainDict, 'allowSpellCheck', 'boolean', !empty($settings['allow_spellcheck']));
    addPlistKey($dom, $mainDict, 'allowDictionaryLookup', 'boolean', !empty($settings['allow_dictionary']));

    
    addPlistKey($dom, $mainDict, 'allowSwitchToApplications', 'boolean', !empty($settings['allow_switch_apps']));
    addPlistKey($dom, $mainDict, 'allowFlashFullscreen', 'boolean', false);
    addPlistKey($dom, $mainDict, 'allowVideoCapture', 'boolean', false);
    addPlistKey($dom, $mainDict, 'allowAudioCapture', 'boolean', false);

    
    addPlistKey($dom, $mainDict, 'examSessionClearCookiesOnEnd', 'boolean', true);
    addPlistKey($dom, $mainDict, 'examSessionClearCookiesOnStart', 'boolean', true);

    
    $urlFilterEnabled = !empty($settings['url_filter_enable']);
    addPlistKey($dom, $mainDict, 'URLFilterEnable', 'boolean', $urlFilterEnabled);

    if ($urlFilterEnabled) {
        addPlistKey($dom, $mainDict, 'URLFilterEnableContentFilter', 'boolean', false);

        
        $key = $dom->createElement('key', 'URLFilterRules');
        $mainDict->appendChild($key);
        $rulesArray = $dom->createElement('array');
        $mainDict->appendChild($rulesArray);

        
        $allowUrls = array_filter(array_map('trim', explode("\n", $settings['url_allowlist'] ?? '')));
        foreach ($allowUrls as $url) {
            if (empty($url)) continue;
            $ruleDict = $dom->createElement('dict');
            $rulesArray->appendChild($ruleDict);
            addPlistKey($dom, $ruleDict, 'action', 'integer', 1); 
            addPlistKey($dom, $ruleDict, 'active', 'boolean', true);
            addPlistKey($dom, $ruleDict, 'expression', 'string', $url);
            addPlistKey($dom, $ruleDict, 'regex', 'boolean', false);
        }

        
        $blockUrls = array_filter(array_map('trim', explode("\n", $settings['url_blocklist'] ?? '')));
        foreach ($blockUrls as $url) {
            if (empty($url)) continue;
            $ruleDict = $dom->createElement('dict');
            $rulesArray->appendChild($ruleDict);
            addPlistKey($dom, $ruleDict, 'action', 'integer', 0); 
            addPlistKey($dom, $ruleDict, 'active', 'boolean', true);
            addPlistKey($dom, $ruleDict, 'expression', 'string', $url);
            addPlistKey($dom, $ruleDict, 'regex', 'boolean', false);
        }
    }

    
    if (!empty($settings['exam_key'])) {
        
        addPlistKey($dom, $mainDict, 'browserExamKey', 'string', $settings['exam_key']);
    }

    return $dom->saveXML();
}


function addPlistKey(DOMDocument $dom, DOMElement $parent, string $keyName, string $type, mixed $value): void {
    $key = $dom->createElement('key', $keyName);
    $parent->appendChild($key);

    switch ($type) {
        case 'string':
            $val = $dom->createElement('string', htmlspecialchars((string)$value, ENT_XML1));
            break;
        case 'integer':
            $val = $dom->createElement('integer', (string)(int)$value);
            break;
        case 'boolean':
            $val = $dom->createElement($value ? 'true' : 'false');
            break;
        case 'real':
            $val = $dom->createElement('real', (string)(float)$value);
            break;
        default:
            $val = $dom->createElement('string', htmlspecialchars((string)$value, ENT_XML1));
    }
    $parent->appendChild($val);
}


function save_seb_xml(int $configId, string $xml): string {
    $dir = SEB_CONFIGS_PATH;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = $configId . '.seb.xml';
    $filepath = $dir . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($filepath, $xml);
    return $filepath;
}







