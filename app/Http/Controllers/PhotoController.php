<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Album;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
    // Liste les photos avec filtrage et tri
    public function index(Request $request)
    {
        // Initialisation de la requête pour récupérer les photos
        $photos = Photo::query();

        // Filtrage par titre
        if ($request->filled('titre')) {
            $photos->where('titre', 'like', '%' . $request->titre . '%');
        }

        // Filtrage par album
        if ($request->filled('album_id')) {
            $photos->where('album_id', $request->album_id);
        }

        // Tri des photos selon les critères 'sort' et 'order' dans la requête
        if ($request->filled('sort') && in_array($request->sort, ['titre', 'note'])) {
            $sortBy = $request->get('sort', 'titre');
            $order = $request->get('order', 'asc');
            $photos->orderBy($sortBy, $order);
        }

        // Pagination des résultats
        $photos = $photos->paginate(10);

        // Récupérer tous les albums pour le filtrage
        $albums = Album::all();

        // Passer les photos et albums à la vue
        return view('photos.index', compact('photos', 'albums'));
    }
// Affiche le formulaire pour modifier un album
public function edit($id)
{
    $photo = Photo::findOrFail($id); // Récupère la photo à modifier
    $albums = Album::all(); // Récupère tous les albums
    return view('photos.edit', compact('photo', 'albums')); // Passe la photo et les albums à la vue
}


    // Affiche le formulaire pour ajouter une photo
    public function create()
    {
        $albums = Album::all();
        $tags = Tag::all(); // Récupérer tous les tags
        return view('photos.create', compact('albums', 'tags'));
    }

    // Dans votre PhotoController.php

public function update(Request $request, $id)
{
    // Validation des champs envoyés par le formulaire
    $request->validate([
        'titre' => 'required|string|max:255',
        'album_id' => 'required|exists:albums,id',
        'file' => 'nullable|image|max:10240', // Validation pour un fichier image (max 10MB)
    ]);

    // Récupération de la photo à mettre à jour
    $photo = Photo::findOrFail($id);

    // Mise à jour des informations de la photo
    $photo->titre = $request->input('titre');
    $photo->album_id = $request->input('album_id');

    // Si un fichier a été téléchargé, on l'enregistre
    if ($request->hasFile('file')) {
        // Supprimer l'ancien fichier (si nécessaire)
        if ($photo->file_path && \Storage::disk('public')->exists($photo->file_path)) {
            \Storage::disk('public')->delete($photo->file_path);
        }

        // Enregistrement du nouveau fichier et mise à jour du chemin
        $path = $request->file('file')->store('photos', 'public');
        $photo->file_path = $path; // Assurez-vous que vous avez une colonne file_path dans la table photos
    }

    // Sauvegarde des modifications
    $photo->save();

    // Redirection vers la liste des photos avec un message de succès
    return redirect()->route('photos.index')
        ->with('success', 'La photo a été mise à jour avec succès.');
}


    public function store(Request $request)
    {
        // Validation des champs
        $request->validate([
            'titre' => 'required|string|max:255',
            'album_id' => 'required|exists:albums,id',
            'file' => 'nullable|url', // Ensure it's a valid URL
            'tags' => 'nullable|array', // Validation pour s'assurer que tags est un tableau
            'tags.*' => 'exists:tags,id', // Validation pour s'assurer que chaque tag existe dans la table tags
        ]);

        // Création de la photo
        $photo = new Photo();
        $photo->titre = $request->titre;
        $photo->album_id = $request->album_id;

        // Si une URL de photo est fournie
        if ($request->has('file')) {
            $photo->url = $request->file; // Store the URL directly
        }

        // Sauvegarde de la photo
        $photo->save();

        // Attacher les tags à la photo (si des tags sont sélectionnés)
        if ($request->has('tags')) {
            $photo->tags()->attach($request->tags); // Ajoute les tags à la photo
        }

        return redirect()->route('photos.index')
            ->with('success', 'Photo ajoutée avec succès !');
    }


    // Affiche une photo spécifique
    public function show($id)
    {
        $photo = Photo::with('tags')->findOrFail($id);
        $tags = Tag::all(); // Récupérer tous les tags disponibles
        return view('photos.show', compact('photo', 'tags'));
    }


    // Supprime une photo
    public function destroy($id)
    {
        $photo = Photo::findOrFail($id);
        $photo->delete();

        return redirect()->route('photos.index')
            ->with('success', 'Photo supprimée avec succès !');
    }
}
