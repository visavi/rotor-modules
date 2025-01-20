<?php

declare(strict_types=1);

namespace Modules\Payment\Controllers;

use App\Classes\Validator;
use App\Http\Controllers\Controller;
use App\Models\PaidAdvert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvertController extends Controller
{
    /**
     * Main page
     */
    public function index(Request $request, Validator $validator): View|RedirectResponse
    {
        $places = PaidAdvert::PLACES;
        //$days = PaidAdvert::DAYS;
        $advert = new PaidAdvert();
        $place = $request->input('place');

        if ($request->isMethod('post')) {
            $site = $request->input('site');
            $names = (array) $request->input('names');
            $color = $request->input('color');
            $bold = empty($request->input('bold')) ? 0 : 1;
            $term = (string) $request->input('term');
            $comment = $request->input('comment');

            $term = strtotime($term);
            $names = array_unique(array_diff($names, ['']));

            $validator->equal($request->input('_token'), csrf_token(), __('validator.token'))
                ->in($place, $places, ['place' => __('admin.paid_adverts.place_invalid')])
                ->url($site, ['site' => __('validator.url')])
                ->length($site, 5, 100, ['site' => __('validator.url_text')])
                ->regex($color, '|^#+[A-f0-9]{6}$|', ['color' => __('validator.color')], false)
                ->gt($term, SITETIME, ['term' => __('admin.paid_adverts.term_invalid')])
                ->length($comment, 0, 255, ['comment' => __('validator.text_long')])
                ->gte(count($names), 1, ['names' => __('admin.paid_adverts.names_count')]);

            foreach ($names as $name) {
                $validator->length($name, 5, 35, ['names' => __('validator.text')]);
            }

            if ($validator->isValid()) {
                PaidAdvert::query()->create([
                    'user_id'    => getUser('id'),
                    'place'      => $place,
                    'site'       => $site,
                    'names'      => array_values($names),
                    'color'      => $color,
                    'bold'       => $bold,
                    'comment'    => $comment,
                    'created_at' => SITETIME,
                    'deleted_at' => $term,
                ]);

                clearCache('paidAdverts');

                return redirect('admin/paid-adverts?place=' . $place)
                    ->with('success', __('main.record_added_success'));
            }

            setInput($request->all());
            setFlash('danger', $validator->getErrors());
        }

        return view('Payment::advert/create', compact('advert', 'places', /*'days', */'place'));
    }
}
