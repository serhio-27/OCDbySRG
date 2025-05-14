<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../vendor/autoload.php');
require_once('db.php');

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$startDate = $data['start_date'];
$endDate = $data['end_date'];
$doctorId = $data['doctor_id'];

// Получаем информацию о враче
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND type = 'doctor'");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Получаем все приемы за выбранный период
$stmt = $db->prepare("
    SELECT a.*, u.surname, u.name, u.patronymic 
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? 
    AND a.status = 'completed'
    AND DATE(a.appointment_date) BETWEEN ? AND ?
");
$stmt->execute([$doctorId, $startDate, $endDate]);
$appointments = $stmt->fetchAll();

// Подсчитываем количество посещений для каждого пациента
$patientVisits = [];
foreach ($appointments as $appointment) {
    $patientKey = $appointment['surname'] . ' ' . $appointment['name'] . ' ' . $appointment['patronymic'];
    if (!isset($patientVisits[$patientKey])) {
        $patientVisits[$patientKey] = 0;
    }
    $patientVisits[$patientKey]++;
}

// Создаем PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// Устанавливаем шрифты для заголовка и футера
$pdf->setHeaderFont(['dejavusans', '', 10]);
$pdf->setFooterFont(['dejavusans', '', 8]);
// Устанавливаем информацию о документе
$pdf->SetCreator('ЦРБ Карасук Онлайн');
$pdf->SetAuthor('ЦРБ Карасук');
$pdf->SetTitle('Отчет о приемах');

// Устанавливаем данные заголовка
$pdf->SetHeaderData('', 0, 'Отчет о приемах', 'ЦРБ Карасук Онлайн');

// Устанавливаем шрифт
$pdf->setFont('dejavusans', '', 10);

// Добавляем страницу
$pdf->AddPage();

// Заголовок отчета
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'Отчет о приемах врача', 0, 1, 'C');
$pdf->Ln(5);

// Информация о враче и периоде
$pdf->SetFont('dejavusans', '', 12);
$pdf->Cell(0, 10, 'Врач: ' . $doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic'], 0, 1);
$pdf->Cell(0, 10, 'Период: с ' . date('d.m.Y', strtotime($startDate)) . ' по ' . date('d.m.Y', strtotime($endDate)), 0, 1);
$pdf->Cell(0, 10, 'Общее количество приемов: ' . count($appointments), 0, 1);
$pdf->Ln(5);

// Таблица посещений по пациентам
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Количество посещений по пациентам:', 0, 1);
$pdf->Ln(2);

// Заголовки таблицы
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(120, 7, 'ФИО пациента', 1, 0, 'C', true);
$pdf->Cell(70, 7, 'Количество посещений', 1, 1, 'C', true);

// Данные таблицы
$pdf->SetFont('dejavusans', '', 10);
foreach ($patientVisits as $patient => $visits) {
    $pdf->Cell(120, 7, $patient, 1, 0, 'L');
    $pdf->Cell(70, 7, $visits, 1, 1, 'C');
}

// Выводим PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Отчет_' . $startDate . '_' . $endDate . '.pdf"');
echo $pdf->Output('', 'S'); 