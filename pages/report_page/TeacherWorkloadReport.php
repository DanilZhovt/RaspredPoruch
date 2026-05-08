<?php

class TeacherWorkloadReport
{
    private array $rows;
    private array $teachers;

    public function __construct(array $rows, array $teachers)
    {
        $this->rows = $rows;
        $this->teachers = $teachers;
    }

    public function build(): array
    {
        $lessonTypes = $this->getLessonTypes();

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

                    foreach ($lessonTypes as $lessonType) {
                        $report[$teacherName][$lessonType] = 0;
                    }
                }

                $report[$teacherName][$type] += $hours;
                $report[$teacherName]['Итого'] += $hours;
            }
        }

        return [
            'lessonTypes' => $lessonTypes,
            'report' => array_values($report),
        ];
    }

    private function getLessonTypes(): array
    {
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