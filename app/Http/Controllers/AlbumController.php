<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    // Liste tous les albums avec tri
    public function index(Request $request)
    {
        // Récupérer tous les albums
        $albums = Album::query();

        // Filtrage par titre (si le champ titre est rempli)
        if ($request->filled('titre')) {
            $albums->where('titre', 'like', '%' . $request->titre . '%');
        }

        // Tri des albums selon le paramètre sort et order de la requête
        if ($request->filled('sort') && in_array($request->sort, ['titre', 'created_at'])) {
            $sortBy = $request->get('sort', 'titre'); // Par défaut, trier par titre
            $order = $request->get('order', 'asc');   // Par défaut, ordre ascendant
            $albums->orderBy($sortBy, $order);
        }

        // Retourner la vue avec les albums
        return view('albums.index', compact('albums'));
    }

    // Affiche le formulaire pour créer un nouvel album
    public function create()
    {
        return view('albums.create');
    }

    // Enregistre un nouvel album
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
        ]);

        // Add 'user_id' and 'creation' when creating the album
        Album::create([
            'titre' => $request->titre,
            'user_id' => auth()->id(),  // Assuming you're using Laravel authentication
        ]);

        return redirect()->route('albums.index')
            ->with('success', 'Album créé avec succès !');
    }


    // Affiche un album spécifique et ses photos
    public function show($id)
    {
        $album = Album::with('photos')->findOrFail($id);

        return view('albums.show', compact('album'));
    }

    // Affiche le formulaire pour modifier un album
    public function edit($id)
    {
        $album = Album::findOrFail($id); // Récupère l'album à modifier
        return view('albums.edit', compact('album')); // Passe l'album à la vue
    }


    // Met à jour un album existant
    public function update(Request $request, $id)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
        ]);

        $album = Album::findOrFail($id);
        $album->update($request->all());

        return redirect()->route('albums.index')
            ->with('success', 'Album mis à jour avec succès !');
    }

    // Supprime un album et ses photos associées
    public function destroy($id)
    {
        $album = Album::findOrFail($id);
        $album->delete();

        return redirect()->route('albums.index')
            ->with('success', 'Album supprimé avec succès !');
    }
}
