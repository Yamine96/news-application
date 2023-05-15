<?php




namespace App\Service;

use App\Repository\CategoryRepository;

class NavCategory
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
      $this->categoryRepository=$categoryRepository;
    }
    public function category():array
    {return $this->categoryRepository->findAll();
    }
}