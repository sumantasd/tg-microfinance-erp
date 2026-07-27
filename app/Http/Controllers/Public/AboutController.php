<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Models\TeamMember;
use App\Models\WebsiteSetting;

class AboutController extends Controller
{
    public function index()
    {
        $settings = WebsiteSetting::first();
        $aboutSection = HomepageSection::where('section_key', 'about_summary')->first();
        $boardMembers = TeamMember::where('status', 'active')->where('type', 'board')->orderBy('display_order', 'asc')->get();
        $managementMembers = TeamMember::where('status', 'active')->where('type', 'management')->orderBy('display_order', 'asc')->get();

        return view('public.about.index', compact('settings', 'aboutSection', 'boardMembers', 'managementMembers'));
    }

    public function mission()
    {
        $settings = WebsiteSetting::first();
        $aboutSection = HomepageSection::where('section_key', 'about_summary')->first();

        return view('public.about.mission', compact('settings', 'aboutSection'));
    }

    public function vision()
    {
        $settings = WebsiteSetting::first();
        $aboutSection = HomepageSection::where('section_key', 'about_summary')->first();

        return view('public.about.vision', compact('settings', 'aboutSection'));
    }

    public function boardOfDirectors()
    {
        $boardMembers = TeamMember::where('status', 'active')
            ->where('type', 'board')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return view('public.about.board-of-directors', compact('boardMembers'));
    }

    public function managementTeam()
    {
        $managementMembers = TeamMember::where('status', 'active')
            ->where('type', 'management')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return view('public.about.management-team', compact('managementMembers'));
    }
}
