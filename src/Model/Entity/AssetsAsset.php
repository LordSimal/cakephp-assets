<?php
declare(strict_types=1);

namespace Assets\Model\Entity;

use Assets\Enum\ImageSizes;
use Assets\Utilities\ImageAsset;
use Cake\ORM\Entity;
use League\Csv\Reader;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

/**
 * AssetsAsset Entity
 *
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property string|null $category
 * @property string $filename
 * @property string $directory
 * @property string $mimetype
 * @property string $filesize
 * @property string $filetype
 * @property string $absolute_path
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class AssetsAsset extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'title' => true,
        'description' => true,
        'category' => true,
        'filename' => true,
        'directory' => true,
        'mimetype' => true,
        'filesize' => true,
        'created' => true,
        'modified' => true,
    ];

    public function exists(): bool
    {
        return file_exists($this->absolute_path);
    }

    protected function _getAbsolutePath(): string
    {
        return ROOT . DS . $this->directory . DS . $this->filename;
    }

    public function read(): string
    {
        if (!$this->exists()) {
            throw new \Exception("The File {$this->filename} for the Asset #{$this->id} ({$this->title}) does not exist in {$this->directory}.");
        }

        return FileSystem::read($this->absolute_path);
    }

    protected function _getPublicFilename(): string
    {
        return Strings::webalize($this->title) . '.' . $this->filetype;
    }

    protected function _getFiletype(): ?string
    {
        return Strings::after($this->filename, '.', -1);
    }

    public function isViewableInBrowser(): bool
    {
        return $this->exists() &&
            match (Strings::before($this->mimetype, '/')) {
                'image', 'video' => true,
                default => false,
            } ||
            match (Strings::after($this->mimetype, '/')) {
                'pdf', 'json' => true,
                default => false,
            };
    }

    public function isImage(): bool
    {
        return $this->exists()
            && Strings::before($this->mimetype, '/') === 'image'
            && !Strings::contains((string)Strings::after($this->mimetype, '/'), 'svg');
    }

    public function isPlainText(): bool
    {
        return $this->exists()
            && Strings::before($this->mimetype, '/') === 'text';
    }

    public function getImage(int $quality=90): ImageAsset
    {
        if (!$this->isImage()) {
            throw new \Exception("Cannot call Asset::getImage() on #{$this->id} ($this->title) with MimeType {$this->mimetype}.");
        }

        return new ImageAsset($this, $quality);
    }

    public function getThumbnail(): ?string
    {
        if (!$this->exists()) {
            return '<span class="error">' . __("Datei nicht gefunden. ") . '</span>';
        }

        if ($this->isImage()) {
            return $this->getImage(65)->scaleWidth(ImageSizes::THMB)->setCSS('asset-thumbnail')->toJpg()->getHTML();
        }

        return null;
    }

    protected function _getFullTitle(): string
    {
        return $this->title . ' (' . $this->mimetype . ')';
    }

    public function getFileSizeInfo(): string
    {
        $filesize = (int)$this->filesize;

        switch ($filesize) {
            case $filesize > 500000000:
                return round($filesize / 1000000000, 1) . ' GB';
            case $filesize > 500000:
                return round($filesize / 1000000, 1) . ' MB';
            case $filesize > 500:
                return round($filesize / 1000, 1) . ' kB';
            default:
                return $filesize . ' Byte';
        }
    }

    /**
     * @throws \League\Csv\InvalidArgument
     * @throws \League\Csv\Exception
     */
    public function getCsvReader(array $options = []): Reader
    {
        if ($this->filetype !== 'csv') {
            throw new \Exception("The Asset {$this->title} is not a csv.");
        }

        $reader = Reader::createFromString($this->read());
        $reader->setDelimiter($options['csv_delimiter'] ?? ';');
        $reader->setHeaderOffset($options['csv_header_offset'] ?? 0);

        return $reader;
    }
}