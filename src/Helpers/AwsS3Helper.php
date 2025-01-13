<?php

namespace Zuoge\LaravelToolsAi\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Uid\Ulid;

class AwsS3Helper
{
    public const array ALLOW_FILE_EXTENSIONS = [
        'webp',
        'svg',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'mp3',
        'mp4',
        'wav',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'pdf',
        'txt',
        'csv'
    ];

    public const array MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf' => 'application/pdf',
    ];

    /**
     * 上传文件
     * @param string $fileName
     * @param string $fileContent
     * @param string|null $acl
     * @return void
     * @throws Exception
     */
    public static function PutObject(string $fileName, string $fileContent, ?string $acl = 'public-read'): void
    {
        $result = Storage::disk('s3')->put($fileName, $fileContent, $acl);
        if (!$result)
            ee('文件上传失败');
    }

    /**
     * 获取文件
     * @param string $fileName 文件名
     * @return string|null
     */
    public static function GetObject(string $fileName): ?string
    {
        return Storage::disk('s3')->get($fileName);
    }

    /**
     * 获取文件访问的 URL
     * @param string $fileName 文件名
     * @param int|null $minutes 有效时间
     * @return string
     */
    public static function TemporaryUrl(string $fileName, ?int $minutes = 30): string
    {
        return Storage::disk('s3')->temporaryUrl($fileName, now()->addMinutes($minutes));
    }

    /**
     * 获取临时上传的 URL
     * @param string $fileName 文件名
     * @param int|null $minutes 有效时间（分钟）
     * @param string|null $acl 访问权限
     * @return array
     */
    public static function TemporaryUploadUrl(string $fileName, ?int $minutes = 30, ?string $acl = 'public-read'): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = self::MIME_TYPES[$extension] ?? 'application/octet-stream'; // 默认 MIME 类型

        $return = Storage::disk('s3')->temporaryUploadUrl(
            $fileName,
            now()->addMinutes($minutes),
            [
                'ACL' => $acl,
            ]
        );
        $return['headers']['Content-Type'] = $mimeType;
        unset($return['headers']['Host']);
        return $return;
    }

    /**
     * 获取上传的参数
     * @param string $uploadDir
     * @param string $fileName
     * @param string|null $acl
     * @return array
     * @throws Exception
     */
    public static function PreUpload(string $uploadDir, string $fileName, ?string $acl = 'public-read'): array
    {
        $fileExt = last(explode('.', $fileName));
        if (!in_array($fileExt, self::ALLOW_FILE_EXTENSIONS))
            ee('文件类型不允许上传');

        $id = Ulid::generate();
        return AwsS3Helper::temporaryUploadUrl("/$uploadDir/$id.$fileExt", acl: $acl);
    }

    /**
     * 富文本中的图片替换为 OSS 地址
     * @param array $params
     * @param array $keys
     * @param string $uploadDir
     * @param int|null $width
     * @param int|null $quality
     * @return void
     * @throws Exception
     */
    public static function ReplaceImageToOss(array &$params, array $keys, string $uploadDir, ?int $width = 1000, ?int $quality = 60): void
    {
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $params[$key] = preg_replace_callback('/(<img[^>]+src=")([^">]+)(")/i', function ($matches) use ($uploadDir, $width, $quality) {
                    if ($matches[2] && strpos($matches[2], 'ata:image')) {
                        // upload base64 image
                        $url = self::uploadBase64Image($matches[2], $uploadDir);
                        logger()->channel('stderr')->debug('Uploaded Base64 Image: ', [
                            'url' => $url
                        ]);

                        // resize
                        $key = self::getKeyByUrl($url);
                        $content = file_get_contents($url . self::getOssParams($width, $quality));
                        self::PutObject($key, $content);
                        logger()->channel('stderr')->debug('Resized Base64 Image: ', [
                            'url' => $url,
                            'width' => $width,
                            'quality' => $quality,
                        ]);

                        // return
                        return $matches[1] . $url . $matches[3];
                    }
                    return $matches[1] . $matches[2] . $matches[3];
                }, $params[$key]);
            }
        }
    }

    /**
     * 图片处理类
     * @param array $images
     * @param int|null $width
     * @param int|null $quality
     * @return void
     * @throws Exception
     */
    public static function CreateThumbUrl(array &$images, ?int $width = 64, ?int $quality = 50): void
    {
        $newImages = [];
        foreach ($images as $image) {
            if (!isset($image['thumbUrl'])) {
                // 如果没有缩略图
                $fileExt = last(explode('.', $image['url']));
                $image['thumbUrl'] = str_replace(".$fileExt", "_thumb.$fileExt", $image['url']);
                $content = file_get_contents($image['url'] . self::getOssParams($width, $quality));
                AwsS3Helper::PutObject(self::getKeyByUrl($image['thumbUrl']), $content);
                logger()->channel('stderr')->debug('Create Thumb Image: ', [
                    'url' => $image['url'],
                    'thumbUrl' => $image['thumbUrl'],
                    'width' => $width,
                    'quality' => $quality,
                ]);
            }
            $newImages[] = $image;
        }
        $images = $newImages;
    }

    /**
     * @param array $images
     * @param int|null $width
     * @param int|null $quality
     * @return void
     * @throws Exception
     */
    public static function ResizeImages(array &$images, ?int $width = 64, ?int $quality = 50): void
    {
        $newImages = [];
        foreach ($images as $image) {
            // 如果有宽度，质量值，则跳过resize
            if (!isset($image['width']) || !isset($image['quality'])) {
                $image['width'] = $width;
                $image['quality'] = $quality;
                $content = file_get_contents($image['url'] . self::getOssParams($width, $quality));
                $key = self::getKeyByUrl($image['url']);

                self::PutObject($key, $content);

                logger()->channel('stderr')->debug('Resize Image: ', [
                    'url' => $image['url'],
                    'width' => $width,
                    'quality' => $quality,
                ]);
            }
            $newImages[] = $image;
        }
        $images = $newImages;
    }

    /**
     * @param int|null $width
     * @param int|null $quality
     * @return string|void
     * @throws Exception
     */
    private static function getOssParams(?int $width, ?int $quality)
    {
        list(, $endpoint) = self::getBucketAndEndpoint();

        if (str_contains($endpoint, 'myqcloud.com')) {
            return "?imageMogr2/thumbnail/{$width}x/quality/$quality/minisize/1";
        } elseif (str_contains($endpoint, 'aliyuncs.com')) {
            return "?x-oss-process=image/resize,w_$width/quality,q_$quality";
        } else {
            ee('暂不支持的云存储');
        }
    }

    /**
     * @param string $url
     * @return string
     * @throws Exception
     */
    private static function getKeyByUrl(string $url): string
    {
        list($bucket, $endpoint) = self::getBucketAndEndpoint();
        return str_replace("https://$bucket.$endpoint", '', $url);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private static function getBucketAndEndpoint(): array
    {
        $endpoint = config('filesystems.disks.s3.endpoint');
        if (!$endpoint)
            ee('请配置云存储的 endpoint');
        $endpoint = str_replace('https://', '', $endpoint);

        $bucket = config('filesystems.disks.s3.bucket');
        if (!$bucket)
            ee('请配置云存储的 bucket');

        return [$bucket, $endpoint];
    }

    /**
     * 上传 base64 图片
     * @param string $content
     * @param string $uploadDir
     * @return string
     * @throws Exception
     */
    private static function uploadBase64Image(string $content, string $uploadDir): string
    {
        $image = explode(',', $content);
        $content = base64_decode($image[1]);

        $extension = substr($image[0], strpos($image[0], "/") + 1);
        $extension = substr($extension, 0, strpos($extension, ";"));

        $id = Ulid::generate();
        $object = "/$uploadDir/$id.$extension";

        self::PutObject($object, $content);
        return Storage::disk('s3')->url($object);
    }
}