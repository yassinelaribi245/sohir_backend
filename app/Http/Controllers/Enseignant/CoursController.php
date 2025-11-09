<?php
namespace App\Http\Controllers\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\Support;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CoursController extends Controller
{
    /* helper: same shape as your Node getCoursWithSupports */
    private function coursWithSupports($id)
    {
        $row = DB::table('cours as c')
            ->leftJoin('supports as s', 'c.id', '=', 's.coursId')
            ->select('c.*', DB::raw("GROUP_CONCAT(s.supportUrl) as supports"))
            ->where('c.id', $id)
            ->groupBy('c.id')
            ->first();

        if (!$row) return null;

        return [
            'id'              => $row->id,
            'titre'           => $row->titre,
            'description'     => $row->description,
            'support'         => $row->supports ? explode(',', $row->supports) : [],
            'dateCreation'    => $row->dateCreation,
            'enseignantId'    => $row->enseignantId,
            'enseignantNom'   => $row->enseignantNom,
            'enseignantPrenom'=> $row->enseignantPrenom,
            'duree'           => $row->duree,
            'niveau'          => $row->niveau,
        ];
    }

    /* CREATE ➜ POST /enseignant/cours */
    public function store(Request $request)
    {
        $request->validate([
            'titre'       => 'required|string|max:191',
            'description' => 'required|string',
            'support'     => 'nullable|string',        // comma-separated URLs
            'duree'       => 'nullable|integer|min:1',
            'niveau'      => 'nullable|string|max:191'
        ]);

        $user = $request->user();
        abort_unless(in_array($user->role, ['teacher','admin']), 403);

        $ens = User::findOrFail($user->id);   // own record

        $cours = Cours::create([
            'titre'            => $request->input('titre'),
            'description'      => $request->input('description'),
            'enseignantId'     => $user->id,
            'enseignantNom'    => $ens->nom  ?? '',
            'enseignantPrenom' => $ens->prenom ?? '',
            'duree'            => $request->input('duree', 60),
            'niveau'           => $request->input('niveau', 'Tous niveaux'),
            'dateCreation'     => now(),
        ]);

        /* supports */
        if ($request->filled('support')) {
            $urls = array_filter(array_map('trim', explode(',', $request->input('support'))));
            $data = array_map(fn($url) => [
                'coursId'     => $cours->id,
                'supportUrl'  => $url,
                'fileName'    => basename($url) ?: 'fichier',
                'fileType'    => 'application/octet-stream',
                'fileSize'    => 0,
            ], $urls);
            DB::table('supports')->insert($data);
        }

        return response()->json($this->coursWithSupports($cours->id), 201);
    }

    /* READ all ➜ GET /enseignant/cours */
    public function index(Request $request)
    {
        $rows = DB::table('cours as c')
            ->leftJoin('supports as s', 'c.id', '=', 's.coursId')
            ->select('c.*', DB::raw("GROUP_CONCAT(s.supportUrl) as supports"))
            ->groupBy('c.id')
            ->orderByDesc('c.dateCreation')
            ->get();

        return $rows->map(fn($row) => [
            'id'              => $row->id,
            'titre'           => $row->titre,
            'description'     => $row->description,
            'support'         => $row->supports ? explode(',', $row->supports) : [],
            'dateCreation'    => $row->dateCreation,
            'enseignantId'    => $row->enseignantId,
            'enseignantNom'   => $row->enseignantNom,
            'enseignantPrenom'=> $row->enseignantPrenom,
            'duree'           => $row->duree,
            'niveau'          => $row->niveau,
        ]);
    }

    /* READ by teacher ➜ GET /enseignant/cours/enseignant/{id} */
    public function byTeacher(Request $request, $enseignantId)
    {
        $rows = DB::table('cours as c')
            ->leftJoin('supports as s', 'c.id', '=', 's.coursId')
            ->select('c.*', DB::raw("GROUP_CONCAT(s.supportUrl) as supports"))
            ->where('c.enseignantId', $enseignantId)
            ->groupBy('c.id')
            ->orderByDesc('c.dateCreation')
            ->get();

        return $rows->map(fn($row) => [
            'id'              => $row->id,
            'titre'           => $row->titre,
            'description'     => $row->description,
            'support'         => $row->supports ? explode(',', $row->supports) : [],
            'dateCreation'    => $row->dateCreation,
            'enseignantId'    => $row->enseignantId,
            'enseignantNom'   => $row->enseignantNom,
            'enseignantPrenom'=> $row->enseignantPrenom,
            'duree'           => $row->duree,
            'niveau'          => $row->niveau,
        ]);
    }

    /* READ one ➜ GET /enseignant/cours/{id} */
    public function show(Request $request, $id)
    {
        $cours = $this->coursWithSupports($id);
        abort_if(!$cours, 404, 'Cours non trouvé');
        return $cours;
    }

    /* UPDATE ➜ PUT /enseignant/cours/{id} */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titre'       => 'sometimes|required|string|max:191',
            'description' => 'sometimes|required|string',
            'support'     => 'nullable|string',
            'duree'       => 'sometimes|integer|min:1',
            'niveau'      => 'sometimes|string|max:191'
        ]);

        $user = $request->user();
        $cours = Cours::findOrFail($id);

        abort_if($cours->enseignantId !== $user->id && $user->role !== 'admin', 403);

        $cours->update($request->only(['titre','description','duree','niveau']));

        /* supports replacement */
        DB::table('supports')->where('coursId', $id)->delete();
        if ($request->filled('support')) {
            $urls = array_filter(array_map('trim', explode(',', $request->input('support'))));
            $data = array_map(fn($url) => [
                'coursId'     => $id,
                'supportUrl'  => $url,
                'fileName'    => basename($url) ?: 'fichier',
                'fileType'    => 'application/octet-stream',
                'fileSize'    => 0,
            ], $urls);
            DB::table('supports')->insert($data);
        }

        return $this->coursWithSupports($id);
    }

    /* DELETE ➜ DELETE /enseignant/cours/{id} */
    public function destroy(Request $request, $id)
    {
        $user  = $request->user();
        $cours = Cours::findOrFail($id);

        abort_if($cours->enseignantId !== $user->id && $user->role !== 'admin', 403);

        DB::table('supports')->where('coursId', $id)->delete();
        $cours->delete();

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }

    /* SEARCH ➜ GET /enseignant/cours/recherche?search=foo&enseignantId=2 */
    public function search(Request $request)
    {
        $request->validate([
            'search'       => 'required|string|max:191',
            'enseignantId' => 'nullable|integer|exists:users,id'
        ]);

        $search = "%{$request->input('search')}%";

        $qb = DB::table('cours as c')
            ->leftJoin('supports as s', 'c.id', '=', 's.coursId')
            ->select('c.*', DB::raw("GROUP_CONCAT(s.supportUrl) as supports"))
            ->where(function ($q) use ($search) {
                $q->where('c.titre', 'like', $search)
                  ->orWhere('c.description', 'like', $search)
                  ->orWhere('c.niveau', 'like', $search);
            })
            ->groupBy('c.id')
            ->orderByDesc('c.dateCreation');

        if ($request->filled('enseignantId')) {
            $qb->where('c.enseignantId', $request->input('enseignantId'));
        }

        $rows = $qb->get();

        return $rows->map(fn($row) => [
            'id'              => $row->id,
            'titre'           => $row->titre,
            'description'     => $row->description,
            'support'         => $row->supports ? explode(',', $row->supports) : [],
            'dateCreation'    => $row->dateCreation,
            'enseignantId'    => $row->enseignantId,
            'enseignantNom'   => $row->enseignantNom,
            'enseignantPrenom'=> $row->enseignantPrenom,
            'duree'           => $row->duree,
            'niveau'          => $row->niveau,
        ]);
    }
}