<?php


namespace MicroweberPackages\Blog;

use Illuminate\Support\Facades\URL;
use MicroweberPackages\Category\Models\Category;

class FrontendFilter
{
    public $queryParams = array();
    protected $pagination;
    protected $query;
    protected $model;

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function pagination($theme = false)
    {
        return $this->pagination->links($theme);
    }

    public function total()
    {
        return $this->pagination->total();
    }

    public function count()
    {
        return $this->pagination->count();
    }

    public function items()
    {
        return $this->pagination->items();
    }

    public function sort($template = false)
    {

        if (!isset($this->model->sortable)) {
            return false;
        }

        $options = [];

        $fullUrl = URL::current();

        $directions = [
          'desc'=>'NEWEST',
          'asc'=>'OLDEST',
        ];

        foreach($this->model->sortable as $field) {
            foreach($directions as $direction=>$directionName) {

                $isActive = 0;
                if ((\Request::get('order') == $direction) && \Request::get('sort') == $field) {
                    $isActive = 1;
                }

                $buildLink = $this->queryParams;
                $buildLink['sort'] = $field;
                $buildLink['order'] = $direction;
                $buildLink = http_build_query($buildLink);

                $pageSort = new \stdClass;
                $pageSort->active = $isActive;
                $pageSort->link = $fullUrl . '?' . $buildLink;
                $pageSort->name = '' . $field .' '. $directionName;

                $options[] = $pageSort;
            }
        }

        return view($template,compact('options'));
    }

    public function categories($template = false)
    {
        $categories = Category::where('parent_id',0)->get();

        return view($template, compact('categories'));
    }

    public function tags($template = false)
    {
        $tags = [];

        $query = $this->model::query();
        $query->with('tagged');
        $results = $query->get();
        if (!empty($results)) {
            foreach($results as $result) {
                foreach($result->tags as $tag) {
                    $tags[$tag->slug] = $tag;
                }
            }
        }

        return view($template, compact('tags'));
    }

    public function limit($template = false)
    {
        $options =[];

        $pageLimits = [
            1,
            2,
            3,
            4,
            5,
        ];

        $fullUrl = URL::current();

        foreach ($pageLimits as $limit) {

            $buildLink = $this->queryParams;
            $buildLink['limit'] = $limit;
            $buildLink = http_build_query($buildLink);

            $isActive = 0;
            if (\Request::get('limit') == $limit) {
                $isActive = 1;
            }

            $pageLimit = new \stdClass;
            $pageLimit->active = $isActive;
            $pageLimit->link = $fullUrl .'?'. $buildLink;
            $pageLimit->name = $limit;

            $options[] = $pageLimit;
        }

        return view($template, compact('options'));
    }

    public function search($template = false)
    {
        $fullUrl = URL::current();

        $searchUri = $this->queryParams;
        $searchUri['search'] = '';
        $searchUri = $fullUrl . '?'. http_build_query($searchUri);

        $search = \Request::get('search', false);

        return view($template, compact('searchUri', 'search'));
    }

    public function results()
    {
        return $this->pagination->items();
    }

    public function apply()
    {
        $limit = \Request::get('limit', false);
        if ($limit) {
            $this->queryParams['limit'] = $limit;
        }

        $page = \Request::get('page', false);
        if ($page) {
            $this->queryParams['page'] = $page;
        }


        // Search
        $search = \Request::get('search');
        if (!empty($search)) {
            $this->query->where('title','LIKE','%'.$search.'%');
        }

        // Sort & Order
        $sort = \Request::get('sort', false);
        $order = \Request::get('order', false);

        if ($sort && $order) {

            $this->queryParams['sort'] = $sort;
            $this->queryParams['order'] = $order;

            $this->query->orderBy($sort, $order);
        }

        // Tags
        $this->query->with('tagged');
        $tags = \Request::get('tags', false);

        if (!empty($tags)) {
            $this->queryParams['tags'] = $tags;
            $this->query->withAllTags($tags);
        }

        $this->pagination = $this->query->paginate($limit)->withQueryString();

        return $this;
    }
}