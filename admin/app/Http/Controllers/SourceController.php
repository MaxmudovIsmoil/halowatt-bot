<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Source;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function store(Request $request, Channel $channel)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url'   => ['required', 'url', 'max:2048'],
        ]);
        $data['type']       = 'rss';
        $data['is_active']  = $request->boolean('is_active', true);
        $data['channel_id'] = $channel->id;
        Source::create($data);
        return back()->with('success', 'RSS manba qo\'shildi.');
    }

    public function update(Request $request, Channel $channel, Source $source)
    {
        abort_if($source->channel_id !== $channel->id, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url'   => ['required', 'url', 'max:2048'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $source->update($data);
        return back()->with('success', 'Manba yangilandi.');
    }

    public function toggle(Channel $channel, Source $source)
    {
        abort_if($source->channel_id !== $channel->id, 403);
        $source->update(['is_active' => !$source->is_active]);
        return back()->with('success', 'Manba holati o\'zgartirildi.');
    }

    public function destroy(Channel $channel, Source $source)
    {
        abort_if($source->channel_id !== $channel->id, 403);
        $source->delete();
        return back()->with('success', 'Manba o\'chirildi.');
    }
}
