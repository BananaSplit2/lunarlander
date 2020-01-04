def determinant(vec1, vec2):
    x1, y1 = vec1
    x2, y2 = vec2

    return x1*y2 - y1*x2

def seg_vers_vec(seg):
    p1, p2 = seg
    x1, y1 = p1
    x2, y2 = p2

    return (x2-x1, y2-y1)

def direction(p1, p2, p3):
    return (p2[1] - p1[1]) * (p3[0] - p2[0]) - (p2[0] - p1[0]) * (p3[1] - p2[1])

def segments_croise(seg1, seg2):
    p1, p2 = seg1
    p3, p4 = seg2

    print(direction(p1, p2, p3))
    print(direction(p1, p2, p4))

    if direction(p1, p2, p3) * direction(p1, p2, p4) < 0:
        return True
        if direction(p3, p4, p1) * direction(p3, p4, p2) < 0:
            return True
    
    return False
    
seg1 = ((0, 1), (2, 1))
seg2 = ((1, 2), (1, 4))

print(segments_croise(seg1, seg2))
