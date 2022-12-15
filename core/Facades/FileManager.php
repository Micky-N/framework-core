<?php

namespace MkyCore\Facades;


use League\Flysystem\DirectoryListing;
use MkyCore\Abstracts\Facade;

/**
 * @method static \MkyCore\FileManager use(string $space)
 * @method static bool fileExists(string $location)
 * @method static bool has(string $location)
 * @method static bool directoryExists(string $location)
 * @method static string read(string $location)
 * @method static resource readStream(string $location)
 * @method static DirectoryListing listContents(string $location, bool $deep = self::LIST_SHALLOW)
 * @method static int lastModified(string $location)
 * @method static int fileSize(string $location)
 * @method static string mimeType(string $location)
 * @method static string visibility(string $location)
 * @method static void write(string $location, string $contents, array $config = [])
 * @method static void writeStream(string $location, $contents, array $config = [])
 * @method static void setVisibility(string $path, string $visibility)
 * @method static void delete(string $location)
 * @method static void deleteDirectory(string $location)
 * @method static void createDirectory(string $location, array $config = [])
 * @method static void move(string $source, string $destination, array $config = [])
 * @method static void copy(string $source, string $destination, array $config = [])
 * @method static string publicUrl(string $path, array $config = [])
 * @method static string temporaryUrl(string $path, DateTimeInterface $expiresAt, array $config = [])
 * @method static string checksum(string $path, array $config = [])
 * @method static array getDrivers()
 * @method static array getFilesystems()
 * @see \MkyCore\FileManager
 */
class FileManager extends Facade
{
    protected static string $accessor = \MkyCore\FileManager::class;
}