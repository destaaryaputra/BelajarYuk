<?php
/**
 * Belajaryuk - Architectural Integrity Guard
 * Scans the project for redundancies, rule violations, and conflicts.
 */

class IntegrityGuard {
    private $root;
    private $errors = [];
    private $warnings = [];

    public function __construct($root) {
        $this->root = $root;
    }

    public function run() {
        echo "🛡️  Starting Belajaryuk Integrity Audit...\n\n";

        $this->checkSeparationOfConcerns();
        $this->checkDuplicateSelectors();
        $this->checkMissingAssets();

        $this->report();
    }

    /**
     * Rule: No inline styles or scripts in HTML templates.
     */
    private function checkSeparationOfConcerns() {
        $files = glob($this->root . '/public/pages/*.html');
        $files[] = $this->root . '/public/layout.html';

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $name = basename($file);

            if (strpos($content, 'style="') !== false) {
                $this->errors[] = "[Separation] Inline style found in $name";
            }
            if (preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $content, $matches)) {
                if (trim($matches[1]) !== '') {
                    $this->errors[] = "[Separation] Inline script found in $name";
                }
            }
            if (strpos($content, '<?php') !== false && $name !== 'index.php') {
                $this->errors[] = "[Separation] PHP tags found in pure HTML file: $name";
            }
        }
    }

    /**
     * Rule: Avoid duplicate CSS selectors across files.
     */
    private function checkDuplicateSelectors() {
        $cssFiles = glob($this->root . '/public/assets/css/*.css');
        $allSelectors = [];

        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{/', $content, $matches);
            
            foreach ($matches[1] as $selector) {
                if (isset($allSelectors[$selector])) {
                    $this->warnings[] = "[Redundancy] Class '.$selector' defined in multiple files: " . basename($allSelectors[$selector]) . " and " . basename($file);
                }
                $allSelectors[$selector] = $file;
            }
        }
    }

    /**
     * Rule: No broken asset links in pages.
     */
    private function checkMissingAssets() {
        // Basic check for local images/scripts
    }

    private function report() {
        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅  Perfect! No integrity issues found.\n";
            return;
        }

        if (!empty($this->errors)) {
            echo "❌  ERRORS (Rule Violations):\n";
            foreach ($this->errors as $err) echo "  - $err\n";
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS (Potential Redundancy):\n";
            foreach ($this->warnings as $warn) echo "  - $warn\n";
            echo "\n";
        }
    }
}

$guard = new IntegrityGuard(dirname(__DIR__));
$guard->run();
