<div class="form-check mb-3">
    <input type="hidden" value="0" name="sets[feed_items_show]">
    <input type="checkbox" class="form-check-input" value="1" name="sets[feed_items_show]" id="feed_items_show"{{ ! empty($settings['feed_items_show']) ? ' checked' : '' }}>
    <label class="form-check-label" for="feed_items_show">{{ __('board::boards.feed_items_show') }}</label>
</div>
