<?php

namespace Plugin\SliderPlugin4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\CategoryRepository;
use Plugin\SliderPlugin4\Repository\SilderCategoryImageRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Util\CacheUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Plugin\SliderPlugin4\Form\Type\Admin\SilderCategoryImageType;
use Plugin\SliderPlugin4\Entity\SilderCategoryImage;

/**
 * Class SliderController
 */
class SliderController extends AbstractController
{

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var SilderCategoryImageRepository
     */
    protected $silderCategoryImageRepository;

    /**
     * SliderController constructor.
     * @param CategoryRepository $categoryRepository
     * @param SilderCategoryImageRepository $silderCategoryImageRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SilderCategoryImageRepository $silderCategoryImageRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->silderCategoryImageRepository = $silderCategoryImageRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/slider/list", name="plugin_slider_list")
     * @Route("/%eccube_admin_route%/slider/category/{parent_id}", requirements={"parent_id" = "\d+"}, name="plugin_slider_category")
     * @Template("@SliderPlugin4/admin/index.twig")
     */
    public function index(Request $request, $parent_id = null, $id = null)
    {
        if ($parent_id) {
            /** @var Category $Parent */
            $Parent = $this->categoryRepository->find($parent_id);
            if (!$Parent) {
                throw new NotFoundHttpException();
            }
        } else {
            $Parent = null;
        }
        if ($id) {
            $TargetCategory = $this->categoryRepository->find($id);
            if (!$TargetCategory) {
                throw new NotFoundHttpException();
            }
            $Parent = $TargetCategory->getParent();
        } else {
            $TargetCategory = new \Eccube\Entity\Category();
            $TargetCategory->setParent($Parent);
            if ($Parent) {
                $TargetCategory->setHierarchy($Parent->getHierarchy() + 1);
            } else {
                $TargetCategory->setHierarchy(1);
            }
        }

        $Categories = $this->categoryRepository->getList($Parent);

        $TopCategories = $this->categoryRepository->getList(null);

        $Ids = [];
        if ($Parent && $Parent->getParents()) {
            foreach ($Parent->getParents() as $item) {
                $Ids[] = $item['id'];
            }
        }
        $Ids[] = intval($parent_id);

        $silderTop = $this->silderCategoryImageRepository->count(['Category' => null]);

        return [
            'Parent' => $Parent,
            'Ids' => $Ids,
            'Categories' => $Categories,
            'TopCategories' => $TopCategories,
            'TargetCategory' => $TargetCategory,
            'silderTop' => $silderTop
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/slider/top_edit", name="plugin_slider_top_edit")
     * @Route("/%eccube_admin_route%/slider/category_edit/{id}", requirements={"id" = "\d+"}, name="plugin_slider_category_edit")
     * @Template("@SliderPlugin4/admin/regist.twig")
     */
    public function edit(Request $request, $id = null, CacheUtil $cacheUtil)
    {
        $Category = null;
        $Parent = null;
        if (!empty($id)) {
            $Category = $this->categoryRepository->find($id);
            if (!$Category) {
                $this->deleteMessage();

                return $this->redirectToRoute('plugin_slider_list');
            }
            $Parent = $Category->getParent();
        }

        $builder = $this->formFactory
            ->createBuilder(SilderCategoryImageType::class, $Category);

        $form = $builder->getForm();

        // ファイルの登録
        $images = [];
        $SilderImages = $this->silderCategoryImageRepository->findBy(['Category' => $Category], ['sort_no' => 'ASC']);
        foreach ($SilderImages as $SilderImage) {
            $images[] = $SilderImage->getFileName();
        }
        $form['images']->setData($images);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $add_images = $form->get('add_images')->getData();
                foreach ($add_images as $add_image) {
                    $SilderCategoryImage = new SilderCategoryImage();
                    $SilderCategoryImage
                        ->setFileName($add_image)
                        ->setCategory($Category)
                        ->setSortNo(1);
                    if ($Category) {
                        $Category->addSilderCategoryImage($SilderCategoryImage);
                    }
                    $this->entityManager->persist($SilderCategoryImage);

                    // 移動
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                $fs = new Filesystem();
                foreach ($delete_images as $delete_image) {
                    $SilderCategoryImage = $this->silderCategoryImageRepository->findOneBy([
                        'Category' => $Category,
                        'file_name' => $delete_image,
                    ]);
                    if ($SilderCategoryImage instanceof SilderCategoryImage) {
                        if ($Category) {
                            $Category->removeSilderCategoryImage($SilderCategoryImage);
                        }
                        $this->entityManager->remove($SilderCategoryImage);
                        $this->entityManager->flush();

                        // If there are no other slider that reference the same image, please delete the image file
                        if (!$this->silderCategoryImageRepository->findOneBy(['file_name' => $delete_image])) {
                            $fs->remove($this->eccubeConfig['eccube_save_image_dir'] . '/' . $delete_image);
                        }

                    } else {
                        // 追加してすぐに削除した画像は、Entityに追加されない
                        $fs->remove($this->eccubeConfig['eccube_temp_image_dir'] . '/' . $delete_image);
                    }
                }

                $this->entityManager->flush();

                $sortNos = $request->get('sort_no_images');
                if ($sortNos) {
                    foreach ($sortNos as $sortNo) {
                        list($filename, $sortNo_val) = explode('//', $sortNo);
                        $SilderCategoryImage = $this->silderCategoryImageRepository
                            ->findOneBy([
                                'file_name' => $filename,
                                'Category' => $Category,
                            ]);
                        $SilderCategoryImage->setSortNo($sortNo_val);
                        $this->entityManager->persist($SilderCategoryImage);
                    }
                }
                $this->entityManager->flush();


                $this->addSuccess('admin.common.save_complete', 'admin');

                if ($form->get('return_link')->getData()) {
                    if($Parent) {
                        return $this->redirectToRoute('plugin_slider_category', ['parent_id' => $Parent->getId()]);
                    } else {
                        return $this->redirectToRoute('plugin_slider_list');
                    }
                }

                $cacheUtil->clearDoctrineCache();

                if($Category) {
                    return $this->redirectToRoute('plugin_slider_category_edit', ['id' => $Category->getId()]);
                } else {
                    return $this->redirectToRoute('plugin_slider_top_edit');
                }
            }
        }


        return [
            'form' => $form->createView(),
            'Parent' => $Parent
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/slider/delete/{id}", requirements={"id" = "\d+"}, name="plugin_slider_delete", methods={"DELETE"})
     */
    public function delete($id = null, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();
        $Category = null;
        $Parent = null;
        if (!empty($id)) {
            $Category = $this->categoryRepository->find($id);
            if (!$Category) {
                $this->deleteMessage();

                return $this->redirectToRoute('plugin_slider_list');
            }
            $Parent = $Category->getParent();
        }
        try {
            $SilderImages = $this->silderCategoryImageRepository->findBy(['Category' => $Category]);
            foreach ($SilderImages as $deleteImage) {
                if ($deleteImage instanceof SilderCategoryImage) {
                    if ($Category) {
                        $Category->removeSilderCategoryImage($deleteImage);
                    }
                    $this->entityManager->remove($deleteImage);
                }
                if ($this->silderCategoryImageRepository->count(['file_name' => $deleteImage->getFileName()]) > 1) {
                    continue;
                }
                try {
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$deleteImage);
                } catch (\Exception $e) {
                    // エラーが発生しても無視する
                }
            }
            $this->entityManager->flush();
            $this->addSuccess('admin.common.delete_complete', 'admin');
            $cacheUtil->clearDoctrineCache();
        } catch (\Exception $e) {
            $this->addError('remove slider error', 'admin');
        }

        if ($Parent) {
            return $this->redirectToRoute('plugin_slider_category', ['parent_id' => $Parent->getId()]);
        } else {
            return $this->redirectToRoute('plugin_slider_list');
        }
    }

    /**
     * @Route("/%eccube_admin_route%/slider/image/add", name="plugin_slider_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('admin_product');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array(strtolower($extension), $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }

        return $this->json(['files' => $files], 200);
    }

}
