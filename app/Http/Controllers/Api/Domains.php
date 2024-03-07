<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project as ModelsProject;
use Illuminate\Http\Request;

class Domains extends Controller
{
    public function domains(Request $request)
    {
        $teamId = get_team_id_from_token();
        if (is_null($teamId)) {
            return response()->json(['error' => 'Invalid token.', 'docs' => 'https://coolify.io/docs/api/authentication'], 400);
        }
        $projects = ModelsProject::where('team_id', $teamId)->get();
        $domains = collect();
        $applications = $projects->pluck('applications')->flatten();
        if ($applications->count() > 0) {
            foreach ($applications as $application) {
                $ip = $application->destination->server->ip;
                $fqdn = str($application->fqdn)->explode(',')->map(function ($fqdn) {
                    return str($fqdn)->replace('http://', '')->replace('https://', '')->replace('/', '');
                });
                $domains->push([
                    'domain' => $fqdn,
                    'ip' => $ip,
                ]);
            }
        }
        $services = $projects->pluck('services')->flatten();
        if ($services->count() > 0) {
            foreach ($services as $service) {
                $service_applications = $service->applications;
                if ($service_applications->count() > 0) {
                    foreach ($service_applications as $application) {
                        $fqdn = str($application->fqdn)->explode(',')->map(function ($fqdn) {
                            return str($fqdn)->replace('http://', '')->replace('https://', '')->replace('/', '');
                        });
                        $domains->push([
                            'domain' => $fqdn,
                            'ip' => $ip,
                        ]);
                    }
                }
            }
        }
        $domains = $domains->groupBy('ip')->map(function ($domain) {
            return $domain->pluck('domain')->flatten();
        });

        return response()->json($domains);
    }
}