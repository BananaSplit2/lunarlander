def affiche_fusee(position, angle):
    """Affiche un rectangle représentant la fusée
    :param position: tuple, représentant la position x, y de la fusée
    :param angle: float, représentant l'angle actuel de la fusée
    """
    x0, y0 = position

    x1 = cos(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2
    y1 = sin(radians(-angle) - atan(1/2)) * hypot(40, 20) / 2

    x2 = cos(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2
    y2 = sin(radians(-angle) + atan(1/2)) * hypot(40, 20) / 2

    poly = [(x0+x1, y0+y1), (x0+x2, y0+y2), (x0-x1, y0-y1), (x0-x2, y0-y2)]
    
    polygone(poly, remplissage='light blue')


def affiche_fusee(position, angle):
    """Affiche un rectangle représentant la fusée
    :param position: tuple, représentant la position x, y de la fusée
    :param angle: float, représentant l'angle actuel de la fusée
    """
    x0, y0 = position

    cercle(x0, y0, 15, remplissage='light blue')
    cercle(x0 - 15*cos(radians(angle)), y0 + 15*sin(radians(angle)), 3, remplissage='black')
