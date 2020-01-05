'''
Prototype Lunar Lander
'''

from upemtk import *
from math import *
from time import sleep
from random import randint
from operator import add

# Constantes
VITESSE_MAX = 6
VITESSE_ANG_MAX = 5
FRAMERATE = 30

def vers_polaire(vecteur):
    """Convertit coordonnées cartésiennes en coordonnées polaires""" 
    x, y = vecteur
    return (hypot(x, y), atan2(y, x))
    
def vers_cartes(vecteur):
    """Convertit coordonnées polaires en coordonnées cartésiennes""" 
    norme, angle = vecteur
    return (norme * cos(angle), norme * sin(angle))

def affiche_fusee(position, angle):
    """Affiche un rectangle représentant la fusée
    :param position: tuple, représentant la position x, y de la fusée
    :param angle: float, représentant l'angle actuel de la fusée
    """   
    polygone(get_coords_fusee(position, angle), remplissage='light blue', tag='s')

def get_coords_fusee(position, angle):
    """Renvoie les coordonnées des 4 coins de la fusée
    :param position: tuple, coordonnées x, y de la fusée
    :param angle: float, angle de la fusée
    :return value: liste de tuples
    """
    x0, y0 = position
    
    x1 = cos(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2
    y1 = sin(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2

    x2 = cos(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2
    y2 = sin(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2

    return [(x0+x1, y0+y1), (x0+x2, y0+y2), (x0-x1, y0-y1), (x0-x2, y0-y2)]

def affiche_fond():
    image(0, 0, 'resources/bg.gif', ancrage='nw')

def affiche_terrain(terrain, points):
    """Affiche le terrain
    :param terrain: liste de tuples
    :param points: bool, si True, affiche individuellement les points"""
    polygone(terrain+[(1200, 800), (0, 800)], remplissage='light grey')

    if points:
        for x, y in terrain:
            cercle(x, y, 5, remplissage='blue')

def affiche_infos(position, angle, vitesse, terrain, carburant, carburant_max, vitesse_max_alu):
    """Affiche une barre en bas de l'écran affichant un certain nombre d'informations
    :param position: tuple, position x,y de la fusée
    :param angle: float, angle de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :param terrain: liste de tuples
    :param carburant: float, carburant restant
    :param carburant_max: float, carburant initial
    """
    rectangle(0, 0, 1200, 100, remplissage='black')

    affiche_vecteur_vitesse(vitesse, vitesse_max_alu)
    affiche_altitude(position, angle, terrain)
    affiche_carburant(carburant, carburant_max)

def affiche_vecteur_vitesse(vitesse, vitesse_max_alu):
    """Affichage du vecteur vitesse dans la barre d'informations
    :param vitesse: tuple, vecteur vitesse de la fusée
    """
    vx, vy = vitesse
    
    cercle(50, 50, 40, couleur='white', tag='s')
    cercle(50, 50, vitesse_max_alu*40/6, remplissage='green', tag='s')
    ligne(10, 50, 90, 50, couleur='white', tag='s')
    ligne(50, 10, 50, 90, couleur='white', tag='s')

    norme = hypot(vx, vy)
    longueur = 40*norme/VITESSE_MAX
    vec_indic = vers_cartes((longueur, vers_polaire(vitesse)[1]))

    fleche(50, 50, 50+vec_indic[0], 50-vec_indic[1], couleur='red', epaisseur=2, tag='s')
    ligne(50, 50, 50+vec_indic[0], 50-vec_indic[1], couleur='red', epaisseur=2, tag='s')

    texte(100, 30, 'Vitesse', couleur='white', ancrage='w', taille=14, tag='s')
    texte(100, 60, str(int(norme*180/VITESSE_MAX)), couleur='white', ancrage='w', taille=20, tag='s')

def affiche_altitude(position, angle, terrain):
    """Affiche de l'altitude de la fusée dans la barre d'informations
    :param position: tuple, position x, y de la fusée
    :param angle: float, angle de la fusée
    :param terrain: liste de tuples
    """
    altitude = get_altitude(position, angle, terrain)
    if altitude < 0:
        altitude = 0

    texte(200, 30, 'Altitude', couleur='white', ancrage='w', taille=14, tag='s')
    texte(200, 60, str(int(altitude)), couleur='white', ancrage='w', taille=20, tag='s')

def affiche_carburant(carburant, carburant_max):
    """Affiche la quantité de carburant restante
    :param carburant: float, carburant restant actuel
    :param carburant_max: float, carburant initial
    """
    texte(360, 15, 'Carburant', ancrage='center', couleur='white', taille=14, tag='s')
    rectangle(350, 90, 370, 90-60*carburant/carburant_max, remplissage='red', tag='s')
    rectangle(350, 90, 370, 90-60, couleur='white', tag='s')

def affiche_explosion(position):
    """Affiche une image d'explosion à la position indiquée
    :param position: tuple, coordonnées x, y
    """
    x, y = position
    image(x, y, 'resources/boom.gif')

def update_propulsion(angle, puiss):
    """Met à jour le vecteur accélération de la propulsion principale de
    la fusée
    :param angle: float, angle de la fusée
    :param puiss: float, ratio de la puissance du réacteur principale
    :return value: tuple, vecteur accélération de la propulsion
    """
    return (0.2 * puiss * cos(radians(angle))), 0.2 * puiss * sin(radians(angle))

def update_vitesse(fusee, vitesse, gravite, propulsion, prop_laterale):
    """Met à jour le vecteur vitesse de la fusée
    :param fusee: tuple, position x, y de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :param gravité: tuple, vecteur accélération de la gravité
    :param propulsion: tuple, vecteur accélération de la propulsion
    :return: tuple, vecteur vitesse mise à jour
    """
    vx, vy = vitesse
    ax, ay = gravite
    px, py = propulsion
    plx, ply = prop_laterale

    somme_x = vx + ax + px + plx
    somme_y = vy + ay + py + ply
    
    if hypot(somme_x, somme_y) <= VITESSE_MAX:
        return (somme_x, somme_y)
    else:
        # Si la vitesse maximale est atteinte, on passe par les coordonnées
        # polaire pour correctement mettre à jour l'angle du vecteur tout en
        # fixant la norme à la vitesse maximale
        return vers_cartes((VITESSE_MAX, vers_polaire((somme_x, somme_y))[1]))

def update_acceleration_angulaire(touche, puiss):
    """Met à jour l'accélération angulaire selon la touche pressée
    :param touche: string, touche du clavier appuyée
    :param puiss: float, ratio de la puissance des réacteurx latéraux
    :return float: accélération angulaire
    """
    if touche == 'Left':
        return 0.2*puiss
    elif touche == 'Right':
        return -0.2*puiss
    else:
        return 0

def update_propulsion_laterale(touche, puiss):
    """Met à jour la propulsion laterale selon la touche pressée
    :param touche: string, touche du clavier appuyée
    :param puiss: float, ratio de la puissance des réacteurs latéraux
    :return float: vecteur accélération de la propulsion latérale
    """
    if touche == 'Left':
        return (-0.2*puiss, 0)
    elif touche == 'Right':
        return (0.2*puiss, 0)
    else:
        return 0

def update_vitesse_angulaire(vitesse_angulaire, acceleration):
    """Met à jour la vitesse angulaire
    :param vitesse_angulaire: float
    :param acceleration: float, accélération angulaire
    :return float:"""
    
    # Si acceleration en cours, on accelere, sauf si la vitesse angulaire
    # maximale est déjà atteinte
    if acceleration != 0:
        if fabs(vitesse_angulaire + acceleration) > VITESSE_ANG_MAX:
            return vitesse_angulaire
        else:
            return vitesse_angulaire + acceleration

    # Si pas d'acceleration en cours, on décélère
    # si la vitesse est déjà assez basse, on arrête le mouvement rotatif
    if fabs(vitesse_angulaire) < 1:
        return 0
    elif vitesse_angulaire < 0:
        return vitesse_angulaire + 0.2
    else:
        return vitesse_angulaire - 0.2

def update_angle(angle, vitesse_angulaire):
    """Met à jour l'angle de la fusée
    :param angle: float
    :param vitesse_angulaire: float
    :return float:
    """
    return angle + vitesse_angulaire

def move_fusee(fusee, vitesse):
    """Met à jour la position de la fusée
    :param fusee: tuple, position x, y de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :return tuple:
    """
    x0, y0 = fusee
    vx, vy = vitesse
    new_x, new_y = x0 + vx, y0 - vy

    # Empêche la fusée sort de l'écran
    if new_x <= 0:
        new_x = 0
    elif new_x >= 1200:
        new_x = 1200

    if new_y <= 100:
        new_y = 100
    
    return (new_x, new_y)

def direction(p1, p2, p3):
    """Evalue l'orientation du point p3 par rapport au segment (p1, p2)
    :param p1: tuple
    :param p2: tuple
    :param p3: tuple
    :return float:
    """
    return (p2[1] - p1[1]) * (p3[0] - p2[0]) - (p2[0] - p1[0]) * (p3[1] - p2[1])

def segments_croise(seg1, seg2):
    """Evalue si deux segments se croisent
    :param seg1: liste de 2 tuples, correspondant aux coordonnées des 2 points formant le segment 1
    :param seg2: liste de 2 tuples, correspondant aux coordonnées des 2 points formant le segment 2
    :return bool:
    """
    p1, p2 = seg1
    p3, p4 = seg2

    if direction(p1, p2, p3) * direction(p1, p2, p4) < 0:
        if direction(p3, p4, p1) * direction(p3, p4, p2) < 0:
            return True
    
    return False
    
def check_gnd_collision(fusee, angle, terrain):
    """Renvoie True en cas de collision entre la fusée et le terrain
    :param fusee: tuple, position x,y de la fusée
    :param angle: float, angle de la fusée
    :param terrain: liste de tuples
    :return bool:
    """
    x0, y0 = fusee

    # Recherche d'une collision avec le segment directement sous la fusée
    i = cherche_segment_plus_proche(x0)
    seg = (terrain[i], terrain[i+1])

    coords = get_coords_fusee(fusee, angle)

    if segments_croise((coords[0], coords[1]), seg):
        return True
    elif segments_croise((coords[1], coords[2]), seg):
        return True
    elif segments_croise((coords[2], coords[3]), seg):
        return True
    elif segments_croise((coords[3], coords[0]), seg):
        return True

    # Recherche d'une collision avec le segment à gauche
    if i > 0:
        seg = (terrain[i-1], terrain[i])
        if segments_croise((coords[0], coords[1]), seg):
            return True
        elif segments_croise((coords[1], coords[2]), seg):
            return True
        elif segments_croise((coords[2], coords[3]), seg):
            return True
        elif segments_croise((coords[3], coords[0]), seg):
            return True

    # Recherche d'une collision avec le segment à droite
    if i < 59:
        seg = (terrain[i+1], terrain[i+2])
        if segments_croise((coords[0], coords[1]), seg):
            return True
        elif segments_croise((coords[1], coords[2]), seg):
            return True
        elif segments_croise((coords[2], coords[3]), seg):
            return True
        elif segments_croise((coords[3], coords[0]), seg):
            return True

    return False

def cherche_segment_plus_proche(x):
    """Renvoie l'indice désignant le premier point du segment de terrain
    directement sous la fusée
    :param x: float, position x de la fusée
    :return int:
    """
    if x < 1200:
        return int(x)//20
    return 59

def check_victoire(position, angle, vitesse, terrain, vitesse_max_alu):
    """Renvoie True si l'atterrissage est correct
    :param position: tuple, position x, y de la fusée
    :param angle: float, angle de la fusée
    :param vitesse: tuple, vecteur vitesse de la fusée
    :param terrain: liste de tuples
    :return bool:
    """
    vx, vy = vitesse
    
    i = cherche_segment_plus_proche(position[0])
    sol = (terrain[i], terrain[i+1])
    p1, p2 = sol
    x0, y0 = p1
    x1, y1 = p2

    # Evalue si la fusée est bien sur le sol
    if get_altitude(position, angle, terrain) < 25:
        # Evalue si le sol est suffisamment plat
        if fabs(degrees(atan(fabs(y0-y1)/fabs(x0-x1)))) <= 5:
            # Evalue si la fusée est suffisament verticale
            if fabs(angle-90) <= 5:
                # Evalue si la vitesse de la fusée est suffisament faible
                if hypot(vx, vy) < vitesse_max_alu:
                    return True

    return False

def get_altitude(position, angle, terrain):
    """Renvoie l'altitude de la fusée
    :param position: tuple, coordonnées x,y de la fusée
    :param angle: float, angle de la fusée
    :terrain: liste de typles
    :returns float:
    """
    x, y = position

    i = cherche_segment_plus_proche(position[0])
    sol = (terrain[i], terrain[i+1])
    p1, p2 = sol
    x0, y0 = p1
    x1, y1 = p2

    # Evalue la position y du sol directement sous la fusée
    inter = fabs(x1-x)/fabs(x1-x0)*fabs(y0-y1)
    y_sol = min(y0, y1)-inter

    coords = get_coords_fusee(position, angle)
    liste_y = []

    for coord in coords:
        liste_y.append(coord[1])

    return y_sol-max(liste_y)

def cree_terrain() :
    x_base = 0
    y_base = 750
    points_terrain = []
    points_terrain.append((x_base, y_base))
    
    
    type_terrain = ['plat','colline','descendant','montant']
    for i in range (12) :
        x = randint(0,3) 
        terrain = type_terrain[x]
        if terrain == 'plat' :
            for i in range (5) :
                x = points_terrain[-1]
                x = tuple(map(add, x, (20,0)))
                points_terrain.append(x)
                
        elif terrain == 'colline' :
            for i in range (3) :
                x = points_terrain[-1]
                
                x = tuple(map(add, x, (20,-2)))
                a, b = x
                if b < 700 :
                    pass
                else :
                    points_terrain.append(x)
            for i in range (2) :
                x = points_terrain[-1]
                
                x = tuple(map(add, x, (20,2)))
                a, b = x
                if b > 795 :
                    pass
                else :
                    points_terrain.append(x)
                
        elif terrain == 'descendant' :
            for i in range (5) :
                x = points_terrain[-1]
                
                x = tuple(map(add, x, (20 ,5)))	
                a, b = x
                if b > 795 :
                    pass
                else :
                    points_terrain.append(x)
                
        elif terrain == 'montant' :
            for i in range (5) :
                x = points_terrain[-1]
                
                x = tuple(map(add, x, (20,-5)))	
                a, b = x
                if b < 700 :
                    pass
                else :
                    points_terrain.append(x)
    print(len(points_terrain))
    return points_terrain

def is_bouton_clique(x1, y1, x2, y2, ev):
    if x1 <= abscisse(ev) <= x2:
        if y1 <= ordonnee(ev) <= y2:
            return True
    return False

def ecran_titre():
    parametres = [2, 1, -0.06, 1]
    mode = 'A'
    quitter = False
    image(0, 0, 'resources/title_screen.gif', ancrage='nw')

    while True:
        # Gestion des évènements/commandes
        ev = attend_ev()
        ev_type = type_ev(ev)

        if ev_type == 'ClicGauche':
            if is_bouton_clique(444, 414, 754, 498, ev):
                return True, parametres, mode
            elif is_bouton_clique(445, 530, 754, 616, ev):
                quitter, parametres, mode = menu_options(parametres, mode)
                if quitter:
                    return False, parametres, mode
                efface_tout()
                image(0, 0, 'resources/title_screen.gif', ancrage='nw')
        elif ev_type == 'Quitte':
            return False, parametres, mode

def menu_options(parametres, mode):
    
    while True:
        # Affichages
        efface_tout()
        image(0, 0, 'resources/options.gif', ancrage='nw')
        affiche_mode(mode)
        affiche_vitesse_max(parametres[0])
        affiche_conso(parametres[1])
        affiche_gravite(parametres[2])
        affiche_puissance(parametres[3])
        
        # Gestion des évènements/commandes
        ev = attend_ev()
        ev_type = type_ev(ev)

        if ev_type == 'ClicGauche':
            # Bouton sortie
            if is_bouton_clique(447, 708, 758, 794, ev):
                return False, parametres, mode
            # Bouton Mode A
            elif is_bouton_clique(278, 205, 520, 269, ev):
                mode = 'A'
            # Bouton Mode B
            elif is_bouton_clique(679, 207, 921, 271, ev):
                mode = 'B'
            # Bouton gauche vitesse max d'alunissage
            elif is_bouton_clique(294, 320, 355, 381, ev):
                parametres[0] = selection(parametres[0], [1, 2, 3], 'Left')
            # Bouton droite vitesse max d'alunissage
            elif is_bouton_clique(837, 323, 898, 384, ev):
                parametres[0] = selection(parametres[0], [1, 2, 3], 'Right')
            # Bouton gauche conso
            elif is_bouton_clique(296, 408, 357, 469, ev):
                parametres[1] = selection(parametres[1], [0, 0.5, 1, 2], 'Left')
            # Bouton droite conso
            elif is_bouton_clique(835, 411, 896, 472, ev):
                parametres[1] = selection(parametres[1], [0, 0.5, 1, 2], 'Right')
            # Bouton gauche gravite
            elif is_bouton_clique(296, 493, 357, 557, ev):
                parametres[2] = selection(parametres[2], [-0.03, -0.06, -0.12], 'Left')
            # Bouton gauche gravite
            elif is_bouton_clique(835, 496, 893, 557, ev):
                parametres[2] = selection(parametres[2], [-0.03, -0.06, -0.12], 'Right')
            # Bouton gauche puissance des propulseurs
            elif is_bouton_clique(297, 588, 358, 649, ev):
                parametres[3] = selection(parametres[3], [0.6, 1, 2], 'Left')
            # Bouton gauche puissances des propulseurs
            elif is_bouton_clique(834, 591, 895, 652, ev):
                parametres[3] = selection(parametres[3], [0.6, 1, 2], 'Right')
        elif ev_type == 'Quitte':
            return True, parametres, mode

def affiche_mode(mode):
    if mode == 'A':
        rectangle(278, 205, 520, 269, remplissage='green')
        rectangle(679, 207, 921, 271, remplissage='red')
    else:
        rectangle(278, 205, 520, 269, remplissage='red')
        rectangle(679, 207, 921, 271, remplissage='green')

def selection(parametre, choix_possibles, direction):
    i = choix_possibles.index(parametre)

    if i == 0 and direction == 'Left':
        return parametre
    elif i == len(choix_possibles)-1 and direction == 'Right':
        return parametre
    elif direction == 'Left':
        return choix_possibles[i-1]
    elif direction == 'Right':
        return choix_possibles[i+1]
    else:
        return parametre

def affiche_vitesse_max(vitesse_max_alu):
    if vitesse_max_alu == 1:
        image(449, 351, 'resources/indic.gif')
    elif vitesse_max_alu == 2:
        image(603, 351, 'resources/indic.gif')
    else:
        image(752, 351, 'resources/indic.gif')

def affiche_conso(conso):
    if conso == 0:
        image(435, 440, 'resources/indic.gif')
    elif conso == 0.5:
        image(549, 440, 'resources/indic.gif')
    elif conso == 1:
        image(663, 440, 'resources/indic.gif')
    else:
        image(772, 440, 'resources/indic.gif')

def affiche_gravite(gravite):
    if gravite == -0.03:
        image(448, 528, 'resources/indic.gif')
    elif gravite == -0.06:
        image(603, 528, 'resources/indic.gif')
    else:
        image(752, 528, 'resources/indic.gif')

def affiche_puissance(puissance):
    if puissance == 0.6:
        image(448, 621, 'resources/indic.gif')
    elif puissance == 1:
        image(603, 621, 'resources/indic.gif')
    else:
        image(752, 621, 'resources/indic.gif')

def game_over(victoire):
    if victoire:
        image(600, 300, 'resources/alunissage.gif', ancrage='center')
    else:
        image(600, 300, 'resources/crash.gif', ancrage='center')

    image(600, 500, 'resources/rejouer.gif', ancrage='center')
    image(600, 600, 'resources/quitter.gif', ancrage='center')

    while True:
        # Gestion des évènements/commandes
        ev = attend_ev()
        ev_type = type_ev(ev)

        if ev_type == 'ClicGauche':
            if is_bouton_clique(445, 458, 755, 542, ev):
                return True
            if is_bouton_clique(445, 558, 755, 642, ev):
                return False
        elif ev_type == 'Quitte':
            return False

if __name__ == '__main__':

    # Création de la fenêtre
    cree_fenetre(1200, 800)
    
    # Parametres de la partie
    # 0 -> vitesse max d'alunissage
    # 1 -> consommation de carburant
    # 2 -> gravité
    # 3 -> force des propulseurs
    parametres = [2, 1, -0.06, 1]

    jouer, parametres, mode = ecran_titre()

    # Boucle principale
    while jouer:

        # Initialisation des variables principales
        fusee_pos = (600, 150)  # Position de la fusee (x, y)
        fusee_angle = 90        # Angle en degrés de la fusée
        fusee_vit = (0, -1)     # Vecteur vitesse de la fusee (x, y)
        fusee_vit_angulaire = 0 # Vitesse angulaire de la fusée
        fusee_accel_angulaire = 0 # Accélération angulaire de la fusée
        gravite = (0, parametres[2])    # Vecteur accélération de la gravité (x, y)
        propulsion = (0, 0)     # Vecteur accélération de la propulsion (x, y)
        prop_laterale = (0, 0)  # Vecteur accélération de la propulsion latérale (x, y)
        carburant_max = 5*30    # Quantité de carburant initiale
        carburant = carburant_max   # Quantité de carburant de la fusée
        terrain = cree_terrain()    # Génération du terrain
        fenetre_fermee = False
        aterri = False

        efface_tout()
        affiche_fond()
        affiche_terrain(terrain, False)
        
        # Boucle principale d'une partie
        while not aterri:
            # Affichages

            efface('s')
            affiche_fusee(fusee_pos, fusee_angle)
            affiche_infos(fusee_pos, fusee_angle, fusee_vit, terrain, carburant, carburant_max, parametres[0])

            mise_a_jour()


            # Gestion des évènements/commandes
            ev = donne_ev()
            ev_type = type_ev(ev)

            if ev_type == 'Quitte':
                fenetre_fermee = True
                ferme_fenetre()
                break

            propulsion = (0, 0)
            if touche_pressee('g') and carburant > 0:
                propulsion = update_propulsion(fusee_angle, parametres[3])
                carburant -= 1*parametres[1]

            if mode == 'A' and carburant > 0:
                if touche_pressee('Left') and touche_pressee('Right'):
                    fusee_accel_angulaire = 0
                elif touche_pressee('Left'):
                    fusee_accel_angulaire = update_acceleration_angulaire('Left', parametres[3])
                    carburant -= 0.5*parametres[1]
                elif touche_pressee('Right'):
                    fusee_accel_angulaire = update_acceleration_angulaire('Right', parametres[3])
                    carburant -= 0.5*parametres[1]
                else:
                    fusee_accel_angulaire = 0
            elif carburant > 0:
                if touche_pressee('Left') and touche_pressee('Right'):
                    prop_laterale = (0, 0)
                elif touche_pressee('Left'):
                    prop_laterale = update_propulsion_laterale('Left', parametres[3])
                    carburant -= 0.5*parametres[1]
                elif touche_pressee('Right'):
                    prop_laterale = update_propulsion_laterale('Right', parametres[3])
                    carburant -= 0.5*parametres[1]
                else:
                    prop_laterale = (0, 0)
            # Mécaniques du jeu
            fusee_vit_angulaire = update_vitesse_angulaire(fusee_vit_angulaire, fusee_accel_angulaire)
            fusee_angle = update_angle(fusee_angle, fusee_vit_angulaire)
            fusee_vit = update_vitesse(fusee_pos, fusee_vit, gravite, propulsion, prop_laterale)
            fusee_pos = move_fusee(fusee_pos, fusee_vit)

            if check_gnd_collision(fusee_pos, fusee_angle, terrain):
                aterri = True

            sleep(1/FRAMERATE)

        if check_victoire(fusee_pos, fusee_angle, fusee_vit, terrain, parametres[0]):
            print('Victoire')
            jouer = game_over(True)
        else:
            affiche_explosion(fusee_pos)
            print('Defeat')
            jouer = game_over(False)

    # Fermeture de la fenêtre
    ferme_fenetre()
