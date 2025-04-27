<?php

// Directorio donde tienes los informes
$reportDir = __DIR__ . '/build/reports';

// Cargar ficheros
$phpunitTestReport = simplexml_load_file("$reportDir/phpunit-test-report.xml");
$phpunitCoverageReport = simplexml_load_file("$reportDir/phpunit-coverage-report.xml");
$phpcsLintReport = simplexml_load_file("$reportDir/phpcs-lint-report.xml");
$psalmSastReport = simplexml_load_file("$reportDir/sast-report.xml");
$psalmStaticAnalysisReport = simplexml_load_file("$reportDir/static-analysis-report.xml");

// Procesar PHPUnit (tests)
$totalTests = (int) $phpunitTestReport['tests'];
$failures = (int) $phpunitTestReport['failures'];
$errors = (int) $phpunitTestReport['errors'];
$skipped = (int) $phpunitTestReport['skipped'];
$passed = $totalTests - $failures - $errors - $skipped;

// Procesar cobertura (Clover)
$coveragePercent = 0;
if (isset($phpunitCoverageReport->project->metrics)) {
    $metrics = $phpunitCoverageReport->project->metrics;
    $coveredElements = (int) $metrics['coveredstatements'];
    $totalElements = (int) $metrics['statements'];
    $coveragePercent = $totalElements > 0 ? round(($coveredElements / $totalElements) * 100, 2) : 0;
}

// Procesar PHPCS (lint)
$lintErrors = 0;
foreach ($phpcsLintReport->file as $file) {
    $lintErrors += count($file->error);
}

// Procesar Psalm SAST (taint analysis)
$sastIssues = 0;
foreach ($psalmSastReport->file as $file) {
    $sastIssues += count($file->issue);
}

// Procesar Psalm Static Analysis (normal quality check)
$staticAnalysisIssues = 0;
foreach ($psalmStaticAnalysisReport->file as $file) {
    $staticAnalysisIssues += count($file->issue);
}

// 💡 Calcular estados antes de escribir HTML
$passedStatus = ($passed === $totalTests) ? 'ok' : 'fail';
$coverageStatus = ($coveragePercent >= 80) ? 'ok' : (($coveragePercent >= 50) ? 'warn' : 'fail');
$lintStatus = ($lintErrors === 0) ? 'ok' : 'fail';
$staticStatus = ($staticAnalysisIssues === 0) ? 'ok' : 'fail';
$sastStatus = ($sastIssues === 0) ? 'ok' : 'fail';

// 🛠️ Generar HTML
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Informe de Calidad del Proyecto</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2em; }
        h1 { color: #333; }
        table { width: 90%; margin: 2em 0; border-collapse: collapse; }
        th, td { padding: 0.5em; border: 1px solid #ccc; text-align: center; }
        .ok { background: #c8e6c9; }
        .warn { background: #fff9c4; }
        .fail { background: #ffcdd2; }
    </style>
</head>
<body>
    <h1>Informe de Calidad del Proyecto</h1>

    <table>
        <thead>
            <tr>
                <th>Indicador</th>
                <th>Valor</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr class="{$passedStatus}">
                <td>Tests superados</td>
                <td>{$passed} / {$totalTests}</td>
                <td>{$passedStatus}</td>
            </tr>
            <tr class="{$coverageStatus}">
                <td>Porcentaje de cobertura</td>
                <td>{$coveragePercent}%</td>
                <td>{$coverageStatus}</td>
            </tr>
            <tr class="{$lintStatus}">
                <td>Errores de estilo (PHPCS)</td>
                <td>{$lintErrors}</td>
                <td>{$lintStatus}</td>
            </tr>
            <tr class="{$staticStatus}">
                <td>Errores de calidad (Psalm Static Analysis)</td>
                <td>{$staticAnalysisIssues}</td>
                <td>{$staticStatus}</td>
            </tr>
            <tr class="{$sastStatus}">
                <td>Errores de seguridad (Psalm SAST)</td>
                <td>{$sastIssues}</td>
                <td>{$sastStatus}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
HTML;

// Guardar HTML
file_put_contents("$reportDir/project-summary.html", $html);

echo "Informe generado en $reportDir/project-summary.html\n";
