<?php

namespace App\Library\Bootstrap;

use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\User;
use App\Library\Domain\Uuid;
use Illuminate\Console\Command;

/** D-10: the initial admin comes from ADMIN_USERNAME / ADMIN_PASSWORD, never from the database seed. */
final class CreateInitialAdmin extends Command
{
    protected $signature = 'library:create-admin';

    protected $description = 'Create the initial admin from environment variables';

    public function handle(UserRepository $users, PasswordHasher $hasher): int
    {
        $username = (string) env('ADMIN_USERNAME');
        $password = (string) env('ADMIN_PASSWORD');
        if ($username === '' || $password === '') {
            $this->error('ADMIN_USERNAME and ADMIN_PASSWORD are required.');

            return self::FAILURE;
        }

        if ($users->findByUsername(User::normalize($username)) === null) {
            $users->add(User::create(Uuid::v4(), $username, $hasher->hash($password), User::ROLE_ADMIN));
        }
        $this->info('Initial admin ready.');

        return self::SUCCESS;
    }
}
