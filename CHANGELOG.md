# Changelog

All notable changes to `laravel-ssl` will be documented in this file.

## 3.0.0 - 2026-04-08

- Drop support for Laravel 11.x
- Unify driver interface with `Certificate` DTO and return `CertificateCollection` instead of arrays.
- Add the `file` driver for parsing local certificates
- Add `ssl:check` Artisan command for bulk SSL certificate monitoring

## 2.1.0 - 2025-03-05

- Update for Laravel 12.x
- Add the 'stream' driver

## 2.0.0 - 2025-01-01

- Update for Laravel 11.x

## 1.0.0 - 2021-10-30

- initial release
