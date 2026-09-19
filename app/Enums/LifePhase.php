<?php

namespace App\Enums;

enum LifePhase: string
{
    case Siswa = 'siswa';
    case Mahasiswa = 'mahasiswa';
    case Pekerja = 'pekerja';
    case Umum = 'umum';

    public function label(): string
    {
        return match ($this) {
            self::Siswa     => 'Siswa',
            self::Mahasiswa => 'Mahasiswa',
            self::Pekerja   => 'Pekerja',
            self::Umum      => 'Umum',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }

    public static function assignableToUserCases(): array
    {
        return [self::Siswa, self::Mahasiswa, self::Pekerja];
    }


    public static function assignableToUserValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::assignableToUserCases());
    }

    public static function assignableToUserOptions(): array
    {
        return collect(self::assignableToUserCases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}