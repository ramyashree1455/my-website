<?php
function normalizeString(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/\.[a-z0-9]+$/', '', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    $value = trim(preg_replace('/\s+/', ' ', $value));
    return $value;
}

function getMedicineImage(string $medicineName): string {
    $baseDir = __DIR__ . '/';
    $defaultImage = 'https://cdn-icons-png.flaticon.com/512/4320/4320337.png';

    $normalizedMedicine = normalizeString($medicineName);
    if ($normalizedMedicine === '') {
        return $defaultImage;
    }

    $imageFiles = glob($baseDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    if (!$imageFiles) {
        return $defaultImage;
    }

    $bestMatch = '';
    $bestScore = 0;

    foreach ($imageFiles as $filePath) {
        $fileName = basename($filePath);
        $normalizedFile = normalizeString($fileName);

        if ($normalizedMedicine === $normalizedFile) {
            return $fileName;
        }

        if (strpos($normalizedFile, $normalizedMedicine) !== false) {
            return $fileName;
        }

        if (strpos($normalizedMedicine, $normalizedFile) !== false) {
            return $fileName;
        }

        $medicineWords = explode(' ', $normalizedMedicine);
        $fileWords = explode(' ', $normalizedFile);
        $matches = count(array_intersect($medicineWords, $fileWords));

        if ($matches > $bestScore) {
            $bestScore = $matches;
            $bestMatch = $fileName;
        }
    }

    return $bestScore > 0 ? $bestMatch : $defaultImage;
}
