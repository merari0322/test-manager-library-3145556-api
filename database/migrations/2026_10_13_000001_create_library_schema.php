<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Initial schema (T-02). Mirrors docs data-model.md sections 3, 4 and 9.
 * No column has a DEFAULT: values come from the domain.
 * ck_book_total_positive and ck_user_role are pending (T-18), so they are not created here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE SCHEMA library;

            CREATE TABLE library.category (
                id   uuid         NOT NULL,
                name varchar(120) NOT NULL,
                CONSTRAINT "PK_category" PRIMARY KEY (id)
            );
            CREATE UNIQUE INDEX ON library.category (name);

            CREATE TABLE library.book (
                id               uuid         NOT NULL,
                title            varchar(250) NOT NULL,
                author           varchar(200) NOT NULL,
                isbn             char(13)     NOT NULL,
                category_id      uuid         NOT NULL,
                total_copies     integer      NOT NULL,
                available_copies integer      NOT NULL,
                cover_key        varchar(512) NULL,
                deleted_at       timestamptz  NULL,
                CONSTRAINT "PK_book" PRIMARY KEY (id),
                FOREIGN KEY (category_id)
                    REFERENCES library.category (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT ck_book_available_range
                    CHECK (available_copies >= 0 AND available_copies <= total_copies)
            );
            CREATE UNIQUE INDEX ON library.book (isbn) WHERE deleted_at IS NULL;
            CREATE INDEX ON library.book (category_id, title) WHERE deleted_at IS NULL;

            CREATE TABLE library.member (
                id              uuid         NOT NULL,
                full_name       varchar(200) NOT NULL,
                document_number varchar(30)  NOT NULL,
                CONSTRAINT "PK_member" PRIMARY KEY (id)
            );
            CREATE UNIQUE INDEX ON library.member (document_number);

            CREATE TABLE library."user" (
                id            uuid         NOT NULL,
                username      varchar(120) NOT NULL,
                password_hash varchar(512) NOT NULL,
                role          varchar(40)  NOT NULL,
                CONSTRAINT "PK_user" PRIMARY KEY (id)
            );
            CREATE UNIQUE INDEX ON library."user" (username);

            CREATE TABLE library.loan (
                id                uuid        NOT NULL,
                member_id         uuid        NOT NULL,
                loaned_by_user_id uuid        NOT NULL,
                loaned_at         timestamptz NOT NULL,
                CONSTRAINT "PK_loan" PRIMARY KEY (id),
                FOREIGN KEY (member_id)
                    REFERENCES library.member (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
                FOREIGN KEY (loaned_by_user_id)
                    REFERENCES library."user" (id) ON DELETE RESTRICT ON UPDATE NO ACTION
            );
            CREATE INDEX ON library.loan (loaned_at);

            CREATE TABLE library.loan_item (
                id            uuid         NOT NULL,
                loan_id       uuid         NOT NULL,
                book_id       uuid         NOT NULL,
                book_title    varchar(250) NOT NULL,
                isbn          char(13)     NOT NULL,
                category_name varchar(120) NOT NULL,
                due_at        timestamptz  NOT NULL,
                returned_at   timestamptz  NULL,
                CONSTRAINT "PK_loan_item" PRIMARY KEY (id),
                FOREIGN KEY (loan_id)
                    REFERENCES library.loan (id) ON DELETE CASCADE ON UPDATE NO ACTION,
                FOREIGN KEY (book_id)
                    REFERENCES library.book (id) ON DELETE RESTRICT ON UPDATE NO ACTION
            );
            CREATE UNIQUE INDEX ON library.loan_item (loan_id, book_id);
            CREATE INDEX ON library.loan_item (book_id);
            CREATE INDEX ON library.loan_item (due_at) WHERE returned_at IS NULL;

            INSERT INTO library.category (id, name) VALUES
                ('11111111-1111-4111-8111-111111111111', 'Novela'),
                ('22222222-2222-4222-8222-222222222222', 'Ciencia'),
                ('33333333-3333-4333-8333-333333333333', 'Tecnología'),
                ('44444444-4444-4444-8444-444444444444', 'Historia'),
                ('55555555-5555-4555-8555-555555555555', 'Infantil');
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP SCHEMA library CASCADE;');
    }
};
