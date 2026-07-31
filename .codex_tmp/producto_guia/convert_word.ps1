$ErrorActionPreference = 'Stop'

$docxPath = (Resolve-Path '.codex_tmp\producto_guia\Guia flujo empresa tramitadores y productos - INSO.docx').Path
$pdfPath = Join-Path (Split-Path $docxPath) 'Guia flujo empresa tramitadores y productos - INSO.pdf'
$statusPath = Join-Path (Split-Path $docxPath) 'conversion_status.txt'

$wordApp = $null
$wordDoc = $null

try {
    Set-Content -LiteralPath $statusPath -Value 'STARTED'
    $wordApp = New-Object -ComObject Word.Application
    $wordApp.Visible = $false
    $wordApp.DisplayAlerts = 0
    $wordDoc = $wordApp.Documents.Open($docxPath, $false, $true)
    $wordDoc.SaveAs2($pdfPath, 17)
    $wordDoc.Close($false)
    $wordDoc = $null
    Set-Content -LiteralPath $statusPath -Value 'COMPLETED'
}
catch {
    Set-Content -LiteralPath $statusPath -Value ('ERROR: ' + $_.Exception.Message)
}
finally {
    if ($wordDoc -ne $null) {
        $wordDoc.Close($false)
    }
    if ($wordApp -ne $null) {
        $wordApp.Quit()
    }
}
