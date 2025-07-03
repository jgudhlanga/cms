<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Helper
{
	public static function generateModelUniqueNumber(Model $model, string $prefix, string $suffix): string
	{
		$id = (int)$model->id > 0 ? $model->id : $model::max('id') + 1;
		return $prefix . $id . $suffix;
	}


	public static function formatDate($stringDate): string
	{
		return Carbon::parse($stringDate)->format('Y-m-d');
	}
	public static function encrypt(string $string): string
	{
		return Crypt::encryptString($string);
	}

	public static function decrypt(string $string): string
	{
		try {
			return Crypt::decryptString($string);
		} catch (DecryptException $e) {
			throw new \RuntimeException('Decryption failed.', 0, $e);
		}
	}

	public static function mask(string $string): string
	{
		return Str::of($string)->mask('*', 2, -2);
	}

    public static function generatePasswordFromName(string $firstName, string $lastName): string
    {
        $firstPart = ucfirst(substr($firstName, 0, 3));  // At least one uppercase
        $lastPart = strtolower(substr($lastName, 0, 3)); // Lowercase
        $number = rand(10, 99);                         // At least one digit
        $specialChars = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $special = $specialChars[array_rand($specialChars)]; // Special character

        // Concatenate to ensure all requirements are met
        $basePassword = "{$firstPart}{$lastPart}{$number}{$special}";

        // Ensure minimum length of 8 characters
        while (strlen($basePassword) < 8) {
            $basePassword .= chr(rand(97, 122)); // add random lowercase letters if needed
        }

        // Shuffle for added security
        return str_shuffle($basePassword);
    }
}
