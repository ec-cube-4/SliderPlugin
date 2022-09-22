<?php

namespace Plugin\SliderPlugin4;

use Eccube\Application;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * @var string コピー元ブロックファイル
     */
    private $originBlock;

    /**
     * @var string ブロック名
     */
    private $blockName = 'Slider_Plugin';

    /**
     * @var string ブロックファイル名
     */
    private $blockFileName = 'slider_block';

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        // コピー元ブロックファイル
        $this->originBlock = __DIR__.'/Resource/template/default/'.$this->blockFileName.'.twig';
    }

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // ブロックの削除
        $this->removeDataBlock($container);
        $this->removeBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta = null, ContainerInterface $container)
    {
        $this->copyBlock($container);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $Block = $entityManager->getRepository(Block::class)->findOneBy([
            'file_name' => $this->blockFileName
        ]);
        
        if (is_null($Block)) {
            // pagelayoutの作成
            $this->createDataBlock($container);
        }
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta = null, ContainerInterface $container)
    {
        $this->removeDataBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta = null, ContainerInterface $container)
    {
        $this->copyBlock($container);
    }

    /**
     * ブロックを登録.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $DeviceType = $entityManager->getRepository(DeviceType::class)->find(DeviceType::DEVICE_TYPE_PC);

        try {
            /** @var Block $Block */
            $Block = $entityManager->getRepository(Block::class)->newBlock($DeviceType);

            // Blockの登録
            $Block->setName($this->blockName)
                ->setFileName($this->blockFileName)
                ->setDeletable(false);
            $entityManager->persist($Block);
            $entityManager->flush($Block);

            // check exists block position
            $blockPos = $entityManager
                ->getRepository(BlockPosition::class)
                ->findOneBy([
                    'Block' => $Block
                ]);
                
            if ($blockPos) {
                return;
            }

            // BlockPositionの登録
            $blockPos = $entityManager->getRepository(BlockPosition::class)->findOneBy(
                [
                    'section' => Layout::TARGET_ID_CONTENTS_TOP, 
                    'layout_id' => Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE
                ],
                ['block_row' => 'DESC']
            );

            $BlockPosition = new BlockPosition();

            // ブロックの順序を変更
            $BlockPosition->setBlockRow(1);
            if ($blockPos) {
                $blockRow = $blockPos->getBlockRow() + 1;
                $BlockPosition->setBlockRow($blockRow);
            }

            $LayoutDefault = $entityManager->getRepository(Layout::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);

            $BlockPosition->setLayout($LayoutDefault)
                ->setLayoutId($LayoutDefault->getId())
                ->setSection(Layout::TARGET_ID_CONTENTS_TOP)
                ->setBlock($Block)
                ->setBlockId($Block->getId());

            $entityManager->persist($BlockPosition);
            $entityManager->flush($BlockPosition);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * ブロックを削除.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $entityManager->getRepository(Block::class)->findOneBy(['file_name' => $this->blockFileName]);

        if (!$Block) {
            return;
        }
        
        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $entityManager->remove($BlockPosition);
            }

            // Blockの削除
            $entityManager->remove($Block);
            $entityManager->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        // ファイルコピー
        $file = new Filesystem();

        if (!$file->exists($templateDir.'/Block/'.$this->blockFileName.'.twig')) {
            // ブロックファイルをコピー
            $file->copy($this->originBlock, $templateDir.'/Block/'.$this->blockFileName.'.twig');
        }
    }

    /**
     * Remove block template.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir.'/Block/'.$this->blockFileName.'.twig');
    }
}
