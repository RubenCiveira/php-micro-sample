<?php

namespace Civi\Ci\Report\Generator;

use Civi\Ci\Report\ReportSummary;

class AdocSummaryReportGenerator implements ReportGeneratorInterface
{
    public function generate(ReportSummary $summary, string $outputPath): void
    {
        $bySeverity = $summary->staticAnalysisReport->getIssuesBySeverity();
        $severityHeader = "";
        foreach($bySeverity as $kind=>$total) {
            $count = count($total);
            $severityHeader .= ""
                . "| Problemas de análisis estático ({$kind})\n"
                . "| {$count}"
                . "";
        }
        $adoc = <<<ADOC
= Resumen de Calidad del Proyecto

== Indicadores generales

[options="header"]
|===
| Indicador | Valor

| Tests totales
| {$summary->testReport->getTotalTests()}

| Tests pasados
| {$summary->testReport->getPassedTests()}

| Porcentaje de cobertura
| {$summary->coverageReport->getOverallCoveragePercent()}%

| Errores de estilo
| {$summary->lintReport->getTotalErrors()}

| Avisos de estilo
| {$summary->lintReport->getTotalWarnings()}

| Problemas de análisis estático
| {$summary->staticAnalysisReport->getTotalIssues()}

{$severityHeader}

| Vulnerabilidades detectadas
| {$summary->sastReport->getTotalIssues()}

|===

ADOC;

        file_put_contents($outputPath, $adoc);
    }
}
