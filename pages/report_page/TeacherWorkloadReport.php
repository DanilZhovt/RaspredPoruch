<?php

class TeacherWorkloadReport
{
    private array $rows;
    private array $teachers;
    private array $report;
    private array $lessonTypes;
    private array $totals;

    public function __construct(array $rows, array $teachers)
    {
        $this->rows = $rows;
        $this->teachers = $teachers;
        $this->report = [];
        $this->lessonTypes = [];
        $this->totals = [];
    }

    public function build(): array
    {
        $this->lessonTypes = $this->getLessonTypes();
        $this->report = $this->buildReport();
        $this->totals = $this->calculateTotals();

        return [
            'lessonTypes' => $this->lessonTypes,
            'report' => $this->report,
            'totals' => $this->totals,
        ];
    }

    public function getReport(): array
    {
        return $this->report;
    }

    public function getLessonTypes(): array
    {
        if (!empty($this->lessonTypes)) {
            return $this->lessonTypes;
        }

        $lessonTypes = [];

        foreach ($this->rows as $row) {
            $type = trim($row['Нагрузка'] ?? '');

            if ($type && !in_array($type, $lessonTypes, true)) {
                $lessonTypes[] = $type;
            }
        }

        sort($lessonTypes);

        return $lessonTypes;
    }

    public function getTotals(): array
    {
        return $this->totals;
    }

    public function getDistributedTotal(): float
    {
        return $this->totals['distributed_total'] ?? 0;
    }

    public function getLoadTotal(): float
    {
        return $this->totals['load_total'] ?? 0;
    }

    public function getDistributedByType(string $type): float
    {
        return $this->totals['distributed_by_type'][$type] ?? 0;
    }

    public function getLoadByType(string $type): float
    {
        return $this->totals['load_by_type'][$type] ?? 0;
    }

    private function buildReport(): array
    {
        $report = [];

        foreach ($this->rows as $row) {
            $type = trim($row['Нагрузка'] ?? '');

            if (empty($row['Сотрудники'])) {
                continue;
            }

            foreach ($row['Сотрудники'] as $employee) {
                $teacherName = trim($employee['Сотрудник'] ?? '');

                if (!$teacherName) {
                    continue;
                }

                $hours = (float)($employee['Количество'] ?? 0);
                $teacherInfo = $this->findTeacher($teacherName);

                if (!isset($report[$teacherName])) {
                    $report[$teacherName] = [
                        'ФИО' => $teacherName,
                        'Ставка' => $teacherInfo['Ставка'] ?? '',
                        'Должность' => $teacherInfo['Должность'] ?? '',
                        'Итого' => 0,
                    ];

                    foreach ($this->lessonTypes as $lessonType) {
                        $report[$teacherName][$lessonType] = 0;
                    }
                }

                $report[$teacherName][$type] += $hours;
                $report[$teacherName]['Итого'] += $hours;
            }
        }

        return array_values($report);
    }

    private function calculateTotals(): array
    {
        $totals = [
            'distributed_total' => 0,
            'load_total' => 0,
            'distributed_by_type' => [],
            'load_by_type' => [],
        ];

        // Инициализация массивов по типам
        foreach ($this->lessonTypes as $type) {
            $totals['distributed_by_type'][$type] = 0;
            $totals['load_by_type'][$type] = 0;
        }

        // Расчет распределенной нагрузки
        foreach ($this->report as $teacher) {
            $totals['distributed_total'] += $teacher['Итого'];

            foreach ($this->lessonTypes as $type) {
                if (isset($teacher[$type]) && $teacher[$type] !== '') {
                    $totals['distributed_by_type'][$type] += (float)$teacher[$type];
                }
            }
        }

        // Расчет общей нагрузки
        foreach ($this->rows as $row) {
            $rowType = trim($row['Нагрузка'] ?? '');

            // Общая нагрузка по всем сотрудникам
            if (!empty($row['Сотрудники'])) {
                foreach ($row['Сотрудники'] as $employee) {
                    $totals['load_total'] += (float)($employee['Количество'] ?? 0);
                }
            }

            // Общая нагрузка по типам
            if (in_array($rowType, $this->lessonTypes)) {
                $totals['load_by_type'][$rowType] += (float)($row['Количество'] ?? 0);
            }
        }

        return $totals;
    }

    private function findTeacher(string $teacherName): ?array
    {
        foreach ($this->teachers as $teacher) {
            if (($teacher['Сотрудник'] ?? '') === $teacherName) {
                return $teacher;
            }
        }

        return null;
    }
}