<?php
require_once __DIR__ . '/../pages/report_page/TeacherWorkloadReport.php';

class TeacherWorkloadReportTest extends PHPUnit\Framework\TestCase
{
    private array $sampleRows;
    private array $sampleTeachers;

    protected function setUp(): void
    {
        $this->sampleRows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '5'],
                    ['Сотрудник' => 'Петров П.П.', 'Количество' => '5'],
                ]
            ],
            [
                'Нагрузка' => 'Практика',
                'Количество' => '20',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '10'],
                    ['Сотрудник' => 'Сидоров С.С.', 'Количество' => '10'],
                ]
            ],
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '15',
                'Сотрудники' => [
                    ['Сотрудник' => 'Петров П.П.', 'Количество' => '15'],
                ]
            ],
        ];

        $this->sampleTeachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
            ['Сотрудник' => 'Петров П.П.', 'Должность' => 'Доцент', 'Ставка' => '0.5'],
            ['Сотрудник' => 'Сидоров С.С.', 'Должность' => 'Ассистент', 'Ставка' => '1.0'],
        ];
    }

    public function testBuildReturnsCorrectStructure(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $result = $report->build();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lessonTypes', $result);
        $this->assertArrayHasKey('report', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testGetLessonTypes(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $lessonTypes = $report->getLessonTypes();

        $this->assertIsArray($lessonTypes);
        $this->assertCount(2, $lessonTypes);
        $this->assertContains('Лекции', $lessonTypes);
        $this->assertContains('Практика', $lessonTypes);
    }

    public function testGetLessonTypesSorted(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Семинар',
                'Количество' => '10',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '10']]
            ],
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '10']]
            ],
            [
                'Нагрузка' => 'Практика',
                'Количество' => '10',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '10']]
            ],
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $lessonTypes = $report->getLessonTypes();
        $this->assertEquals('Лекции', $lessonTypes[0]);
        $this->assertEquals('Практика', $lessonTypes[1]);
        $this->assertEquals('Семинар', $lessonTypes[2]);
    }

    public function testGetReportWithCorrectTeacherData(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $reportData = $report->getReport();
        $this->assertCount(3, $reportData);

        $ivanov = $this->findTeacherByName($reportData, 'Иванов И.И.');
        $this->assertNotNull($ivanov);
        $this->assertEquals('Профессор', $ivanov['Должность']);
        $this->assertEquals('1.0', $ivanov['Ставка']);
        $this->assertEquals(15, $ivanov['Итого']);
        $this->assertEquals(5, $ivanov['Лекции']);
        $this->assertEquals(10, $ivanov['Практика']);

        $petrov = $this->findTeacherByName($reportData, 'Петров П.П.');
        $this->assertNotNull($petrov);
        $this->assertEquals('Доцент', $petrov['Должность']);
        $this->assertEquals('0.5', $petrov['Ставка']);
        $this->assertEquals(20, $petrov['Итого']);
        $this->assertEquals(20, $petrov['Лекции']);
        $this->assertEquals(0, $petrov['Практика']);
    }

    public function testGetDistributedTotal(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $this->assertEquals(45, $report->getDistributedTotal());
    }

    public function testGetLoadTotal(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $this->assertEquals(45, $report->getLoadTotal());
    }

    public function testGetDistributedByType(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $this->assertEquals(25, $report->getDistributedByType('Лекции'));
        $this->assertEquals(20, $report->getDistributedByType('Практика'));
        $this->assertEquals(0, $report->getDistributedByType('Несуществующий тип'));
    }

    public function testGetLoadByType(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $this->assertEquals(25, $report->getLoadByType('Лекции'));
        $this->assertEquals(20, $report->getLoadByType('Практика'));
        $this->assertEquals(0, $report->getLoadByType('Несуществующий тип'));
    }

    public function testGetTotals(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);
        $report->build();

        $totals = $report->getTotals();

        $this->assertIsArray($totals);
        $this->assertArrayHasKey('distributed_total', $totals);
        $this->assertArrayHasKey('load_total', $totals);
        $this->assertArrayHasKey('distributed_by_type', $totals);
        $this->assertArrayHasKey('load_by_type', $totals);
        $this->assertEquals(45, $totals['distributed_total']);
        $this->assertEquals(45, $totals['load_total']);
    }

    public function testBuildWithEmptyData(): void
    {
        $report = new TeacherWorkloadReport([], []);
        $result = $report->build();

        $this->assertIsArray($result);
        $this->assertEmpty($result['report']);
        $this->assertEmpty($result['lessonTypes']);
        $this->assertEquals(0, $result['totals']['distributed_total']);
        $this->assertEquals(0, $result['totals']['load_total']);
    }

    public function testBuildWithEmptyEmployees(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => []
            ]
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $result = $report->build();

        $this->assertIsArray($result);
        $this->assertEmpty($result['report']);
        $this->assertEquals(0, $result['totals']['distributed_total']);
        $this->assertNotEmpty($result['lessonTypes']);
    }

    public function testBuildWithEmptyEmployeeName(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => [
                    ['Сотрудник' => '', 'Количество' => '5'],
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '5'],
                ]
            ]
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $reportData = $report->getReport();
        $this->assertCount(1, $reportData);
        $this->assertEquals('Иванов И.И.', $reportData[0]['ФИО']);
        $this->assertEquals(5, $reportData[0]['Итого']);
    }

    public function testBuildWithUnknownTeacher(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => [
                    ['Сотрудник' => 'Новый Преподаватель', 'Количество' => '10'],
                ]
            ]
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $reportData = $report->getReport();
        $this->assertCount(1, $reportData);
        $this->assertEquals('Новый Преподаватель', $reportData[0]['ФИО']);
        $this->assertEquals('', $reportData[0]['Должность']);
        $this->assertEquals('', $reportData[0]['Ставка']);
    }

    public function testBuildWithFloatingPointNumbers(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10.5',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '5.5'],
                    ['Сотрудник' => 'Петров П.П.', 'Количество' => '5.0'],
                ]
            ],
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $this->assertEquals(10.5, $report->getDistributedTotal());
        $this->assertEquals(10.5, $report->getLoadTotal());
    }

    public function testBuildAggregatesMultipleEntriesForSameTeacher(): void
    {
        $rows = [
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '10',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '3'],
                ]
            ],
            [
                'Нагрузка' => 'Лекции',
                'Количество' => '15',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '7'],
                ]
            ],
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $reportData = $report->getReport();
        $this->assertCount(1, $reportData);
        $this->assertEquals(10, $reportData[0]['Лекции']);
        $this->assertEquals(10, $reportData[0]['Итого']);
    }

    public function testGetReportBeforeBuildReturnsEmpty(): void
    {
        $report = new TeacherWorkloadReport($this->sampleRows, $this->sampleTeachers);

        $this->assertEmpty($report->getReport());
        $this->assertEquals(0, $report->getDistributedTotal());
        $this->assertEquals(0, $report->getLoadTotal());
        $this->assertEmpty($report->getTotals());
    }

    public function testBuildWithMissingLoadType(): void
    {
        $rows = [
            [
                'Нагрузка' => '',
                'Количество' => '10',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '10'],
                ]
            ]
        ];

        $report = new TeacherWorkloadReport($rows, $this->sampleTeachers);
        $report->build();

        $lessonTypes = $report->getLessonTypes();
        $this->assertEmpty($lessonTypes);
    }

    private function findTeacherByName(array $reportData, string $name): ?array
    {
        foreach ($reportData as $teacher) {
            if ($teacher['ФИО'] === $name) {
                return $teacher;
            }
        }
        return null;
    }
}