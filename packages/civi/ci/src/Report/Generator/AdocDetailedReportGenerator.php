<?php

namespace Civi\Ci\Report\Generator;

use Civi\Ci\Report\ReportSummary;
use Civi\Ci\Report\Result\TestCaseResult;
use Civi\Ci\Report\Result\CoverageFileResult;
use Civi\Ci\Report\Result\LintError;
use Civi\Ci\Report\Result\StaticAnalysisIssue;
use Civi\Ci\Report\Result\SastIssue;

class AdocDetailedReportGenerator implements ReportGeneratorInterface
{
    public function generate(ReportSummary $summary, string $outputPath): void
    {
        $adoc = <<<ADOC
= Informe Detallado de Calidad del Proyecto

== Resumen General

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

| Problemas de análisis estático
| {$summary->staticAnalysisReport->getTotalIssues()}

| Vulnerabilidades detectadas
| {$summary->sastReport->getTotalIssues()}

|===

== Detalle de Tests

[options="header"]
|===
| Nombre del Test | Clase | Fichero | Tiempo (s) | Estado
ADOC;

        foreach ($summary->testReport->testCases as $test) {
            /** @var TestCaseResult $test */
            $status = $test->skipped ? 'SKIPPED' : ($test->passed ? 'PASSED' : 'FAILED');
            $adoc .= <<<ADOC

| {$test->name}
| {$test->class}
| {$test->file}
| {$test->time}
| {$status}
ADOC;
        }

        $adoc .= <<<ADOC

|===

== Detalle de Cobertura

[options="header"]
|===
| Fichero | Porcentaje de Cobertura
ADOC;

        foreach ($summary->coverageReport->files as $file) {
            /** @var CoverageFileResult $file */
            $adoc .= <<<ADOC

| {$file->file}
| {$file->getCoveragePercent()}%
ADOC;
        }

        $adoc .= <<<ADOC

|===

== Errores de Estilo (Lint)

[options="header"]
|===
| Fichero | Línea | Severidad | Mensaje
ADOC;

        foreach ($summary->lintReport->errors as $error) {
            /** @var LintError $error */
            $adoc .= <<<ADOC

| {$error->file}
| {$error->line}
| {$error->severity}
| {$error->message}
ADOC;
        }

        $adoc .= <<<ADOC

|===

== Problemas de Análisis Estático

[options="header"]
|===
| Fichero | Línea | Tipo | Mensaje
ADOC;

        foreach ($summary->staticAnalysisReport->issues as $issue) {
            /** @var StaticAnalysisIssue $issue */
            $adoc .= <<<ADOC

| {$issue->file}
| {$issue->line}
| {$issue->type}
| {$issue->message}
ADOC;
        }

        $adoc .= <<<ADOC

|===

== Vulnerabilidades (SAST)

[options="header"]
|===
| Fichero | Línea | Tipo | Mensaje
ADOC;

        foreach ($summary->sastReport->issues as $issue) {
            /** @var SastIssue $issue */
            $adoc .= <<<ADOC

| {$issue->file}
| {$issue->line}
| {$issue->type}
| {$issue->message}
ADOC;
        }

        $adoc .= <<<ADOC

|===

ADOC;

        file_put_contents($outputPath, $adoc);
    }
}
