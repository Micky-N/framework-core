<?php

namespace MkyCore;

use DateTimeInterface;
use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use League\Flysystem\PathNormalizer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMountFilesystem;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToResolveFilesystemMount;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use MkyCore\Application;
use MkyCore\Facades\Request;
use MkyCore\FileSystems\LocalFileSystem;
use Exception;

class FileManager implements FilesystemOperator
{

    /**
     * @var array<string, FilesystemOperator>
     */
    private $filesystems = [];

    private string $prefix;
    private array $drivers = [
        'local' => LocalFileSystem::class
    ];
    private array $fs;

    public function __construct(string $space, array $config)
    {
        $this->prefix = $space.'://';
        $filesystems[$space] = new LocalFileSystem($config);
        $filesystems['base'] = new LocalFileSystem([
            'driver' => 'local',
            'root' => path(),
            'url' => null,
            'visibility' => 'public',
        ]);
        
        $this->fs = $filesystems;

        $this->mountFilesystems($filesystems);
    }

    public function use(string $space)
    {
        $config = \MkyCore\Facades\Config::get('filesystems.spaces.'.$space);
        $this->prefix = $space.'://';
        return new static($space, $config);
    }

    public function fileExists(string $location): bool
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->fileExists($path);
        } catch (Throwable $exception) {
            throw UnableToCheckFileExistence::forLocation($location, $exception);
        }
    }

    public function has(string $location): bool
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->fileExists($path) || $filesystem->directoryExists($path);
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($location, $exception);
        }
    }

    public function directoryExists(string $location): bool
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->directoryExists($path);
        } catch (Throwable $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($location, $exception);
        }
    }

    public function read(string $location): string
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->read($path);
        } catch (UnableToReadFile $exception) {
            throw UnableToReadFile::fromLocation($location, $exception->reason(), $exception);
        }
    }

    public function readStream(string $location)
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->readStream($path);
        } catch (UnableToReadFile $exception) {
            throw UnableToReadFile::fromLocation($location, $exception->reason(), $exception);
        }
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path, $mountIdentifier] = $this->determineFilesystemAndPath($location);

        return
            $filesystem
                ->listContents($path, $deep)
                ->map(
                    function (StorageAttributes $attributes) use ($mountIdentifier) {
                        return $attributes->withPath(sprintf('%s://%s', $mountIdentifier, $attributes->path()));
                    }
                );
    }

    public function lastModified(string $location): int
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->lastModified($path);
        } catch (UnableToRetrieveMetadata $exception) {
            throw UnableToRetrieveMetadata::lastModified($location, $exception->reason(), $exception);
        }
    }

    public function fileSize(string $location): int
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->fileSize($path);
        } catch (UnableToRetrieveMetadata $exception) {
            throw UnableToRetrieveMetadata::fileSize($location, $exception->reason(), $exception);
        }
    }

    public function mimeType(string $location): string
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->mimeType($path);
        } catch (UnableToRetrieveMetadata $exception) {
            throw UnableToRetrieveMetadata::mimeType($location, $exception->reason(), $exception);
        }
    }

    public function visibility(string $location): string
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            return $filesystem->visibility($path);
        } catch (UnableToRetrieveMetadata $exception) {
            throw UnableToRetrieveMetadata::visibility($location, $exception->reason(), $exception);
        }
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            $filesystem->write($path, $contents, $config);
        } catch (UnableToWriteFile $exception) {
            throw UnableToWriteFile::atLocation($location, $exception->reason(), $exception);
        }
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);
        $filesystem->writeStream($path, $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if(!str_contains($path, '://')){
            $path = $this->prefix.$path;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($path);
        $filesystem->setVisibility($path, $visibility);
    }

    public function delete(string $location): void
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            $filesystem->delete($path);
        } catch (UnableToDeleteFile $exception) {
            throw UnableToDeleteFile::atLocation($location, '', $exception);
        }
    }

    public function deleteDirectory(string $location): void
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            $filesystem->deleteDirectory($path);
        } catch (UnableToDeleteDirectory $exception) {
            throw UnableToDeleteDirectory::atLocation($location, '', $exception);
        }
    }

    public function createDirectory(string $location, array $config = []): void
    {
        if(!str_contains($location, '://')){
            $location = $this->prefix.$location;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($location);

        try {
            $filesystem->createDirectory($path, $config);
        } catch (UnableToCreateDirectory $exception) {
            throw UnableToCreateDirectory::dueToFailure($location, $exception);
        }
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        if(!str_contains($source, '://')){
            $source = $this->prefix.$source;
        }

        if(!str_contains($destination, '://')){
            $destination = 'base://'.$destination;
        }elseif(str_contains($destination, '://')){
            $space = explode('://', $destination);
            $space = $space[0];
            if(!$this->hasSpace($space)){
                $fileSystemAdapter = $this->get($space);
                $this->mountFilesystem($space, $fileSystemAdapter);
            }
        }

        /** @var FilesystemOperator $sourceFilesystem */
        /* @var FilesystemOperator $destinationFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($source);
        [$destinationFilesystem, $destinationPath] = $this->determineFilesystemAndPath($destination);

        $sourceFilesystem === $destinationFilesystem ? $this->moveInTheSameFilesystem(
            $sourceFilesystem,
            $sourcePath,
            $destinationPath,
            $source,
            $destination
        ) : $this->moveAcrossFilesystems($source, $destination, $config);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        if(!str_contains($source, '://')){
            $source = $this->prefix.$source;
        }

        if(!str_contains($destination, '://')){
            $destination = 'base://'.$destination;
        }elseif(str_contains($destination, '://')){
            $space = explode('://', $destination);
            $space = $space[0];
            if(!$this->hasSpace($space)){
                $fileSystemAdapter = $this->get($space);
                $this->mountFilesystem($space, $fileSystemAdapter);
            }
        }

        /** @var FilesystemOperator $sourceFilesystem */
        /* @var FilesystemOperator $destinationFilesystem */
        [$sourceFilesystem, $sourcePath] = $this->determineFilesystemAndPath($source);
        [$destinationFilesystem, $destinationPath] = $this->determineFilesystemAndPath($destination);

        $sourceFilesystem === $destinationFilesystem ? $this->copyInSameFilesystem(
            $sourceFilesystem,
            $sourcePath,
            $destinationPath,
            $source,
            $destination
        ) : $this->copyAcrossFilesystem(
            $config['visibility'] ?? null,
            $sourceFilesystem,
            $sourcePath,
            $destinationFilesystem,
            $destinationPath,
            $source,
            $destination
        );
    }

    public function publicUrl(string $path, array $config = []): string
    {
        if(!str_contains($path, '://')){
            $path = $this->prefix.$path;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($path);

        if ( ! method_exists($filesystem, 'publicUrl')) {
            throw new UnableToGeneratePublicUrl(sprintf('%s does not support generating public urls.', $filesystem::class), $path);
        }

        return $filesystem->publicUrl($path, $config);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, array $config = []): string
    {
        if(!str_contains($path, '://')){
            $path = $this->prefix.$path;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($path);

        if ( ! method_exists($filesystem, 'temporaryUrl')) {
            throw new UnableToGenerateTemporaryUrl(sprintf('%s does not support generating public urls.', $filesystem::class), $path);
        }

        return $filesystem->temporaryUrl($path, $expiresAt, $config);
    }

    public function checksum(string $path, array $config = []): string
    {
        if(!str_contains($path, '://')){
            $path = $this->prefix.$path;
        }

        /** @var FilesystemOperator $filesystem */
        [$filesystem, $path] = $this->determineFilesystemAndPath($path);

        if ( ! method_exists($filesystem, 'checksum')) {
            throw new UnableToProvideChecksum(sprintf('%s does not support providing checksums.', $filesystem::class), $path);
        }

        return $filesystem->checksum($path, $config);
    }

    public function get(string $space)
    {
        $fileSystem = null;
        if($this->hasSpace($space)){
            $fileSystem = $this->filesystems[$space];
        }else{
            $fileSystem = $this->resolve($space);
        }
        return $fileSystem;
    }

    private function resolve(string $space): FilesystemOperator
    {
        $config = \MkyCore\Facades\Config::get('filesystems.spaces.'.$space);
        if(!$config){
            throw new Exception("Config not found for the space \"$space\"");
        }
        $driver = $config['driver'] ?? '';
        if(!$this->driverExists($driver)){
            throw new Exception("This driver \"$driver\" is not supported");
        }

        $filesystem = $this->drivers[$driver];
        if(!class_exists($filesystem)){
            throw new Exception("Class $filesystem not exists");
        }

        $filesystem = new $filesystem($config);

        if(!($filesystem instanceof FilesystemOperator)){
            throw new Exception("The filesystem must be an instance of FilesystemOperator");
        }

        $this->filesystems[$space] = $filesystem;
        return $filesystem;

    }
    
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    public function getFilesystems(): array
    {
        return $this->filesystems;
    }

    private function driverExists(string $driver): bool
    {
        return isset($this->drivers[$driver]);
    }

    private function mountFilesystems(array $filesystems): void
    {
        foreach ($filesystems as $key => $filesystem) {
            $this->guardAgainstInvalidMount($key, $filesystem);
            /* @var string $key */
            /* @var FilesystemOperator $filesystem */
            $this->mountFilesystem($key, $filesystem);
        }
    }

    /**
     * @param mixed $key
     * @param mixed $filesystem
     */
    private function guardAgainstInvalidMount($key, $filesystem): void
    {
        if ( ! is_string($key)) {
            throw UnableToMountFilesystem::becauseTheKeyIsNotValid($key);
        }

        if ( ! $filesystem instanceof FilesystemOperator) {
            throw UnableToMountFilesystem::becauseTheFilesystemWasNotValid($filesystem);
        }
    }

    private function mountFilesystem(string $key, FilesystemOperator $filesystem): void
    {
        $this->filesystems[$key] = $filesystem;
    }

    /**
     * @param string $path
     *
     * @return array{0:FilesystemOperator, 1:string}
     */
    private function determineFilesystemAndPath(string $path): array
    {
        if (strpos($path, '://') < 1) {
            throw UnableToResolveFilesystemMount::becauseTheSeparatorIsMissing($path);
        }

        /** @var string $mountIdentifier */
        /** @var string $mountPath */
        [$mountIdentifier, $mountPath] = explode('://', $path, 2);

        if ( ! array_key_exists($mountIdentifier, $this->filesystems)) {
            throw UnableToResolveFilesystemMount::becauseTheMountWasNotRegistered($mountIdentifier);
        }

        return [$this->filesystems[$mountIdentifier], $mountPath, $mountIdentifier];
    }

    private function copyInSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath,
        string $source,
        string $destination
    ): void {
        try {
            $sourceFilesystem->copy($sourcePath, $destinationPath);
        } catch (UnableToCopyFile $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function copyAcrossFilesystem(
        ?string $visibility,
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        FilesystemOperator $destinationFilesystem,
        string $destinationPath,
        string $source,
        string $destination
    ): void {
        try {
            $visibility = $visibility ?? $sourceFilesystem->visibility($sourcePath);
            $stream = $sourceFilesystem->readStream($sourcePath);
            $destinationFilesystem->writeStream($destinationPath, $stream, compact('visibility'));
        } catch (UnableToRetrieveMetadata | UnableToReadFile | UnableToWriteFile $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function moveInTheSameFilesystem(
        FilesystemOperator $sourceFilesystem,
        string $sourcePath,
        string $destinationPath,
        string $source,
        string $destination
    ): void {
        try {
            $sourceFilesystem->move($sourcePath, $destinationPath);
        } catch (UnableToMoveFile $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function moveAcrossFilesystems(string $source, string $destination, array $config = []): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (UnableToCopyFile | UnableToDeleteFile $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function hasSpace(string $space): bool
    {
        return isset($this->filesystems[$space]);
    }

}