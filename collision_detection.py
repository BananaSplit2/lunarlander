# def segments_croise(seg1, seg2):
    # p1, p2 = seg1
    # p3, p4 = seg2

    # x1, y1 = p1
    # x2, y2 = p2
    # x3, y3 = p3
    # x4, y4 = p4
    
    # if (max(x1,x2) < min(x3,x4)):
        # return False

    # a1 = (y1-y2)/(x1-x2)  # Pay attention to not dividing by zero
    # a2 = (y3-y4)/(x3-x4)  # Pay attention to not dividing by zero
    # b1 = y1-a1*x1
    # b2 = y3-a2*x3

    # if a1 == a2:
        # return False  # Parallel segments

    # xa = (b2 - b1) / (a1 - a2)

    # if (xa < max(min(x1,x2), min(x3,x4))) or (xa > min(max(x1,x2), max(x3,x4))):
        # return False  # intersection is out of bound
    # else:
        # return True

def determinant(vec1, vec2):
    x1, y1 = vec1
    x2, y2 = vec2

    return x1*y2 - y1*x2

def seg_vers_vec(seg):
    p1, p2 = seg
    x1, y1 = p1
    x2, y2 = p2

    return (x2-x1, y2-y1)

def segments_croise(seg1, seg2):
    p1, p2 = seg1
    p3, p4 = seg2

    vec1 = seg_vers_vec((p1, p3))
    vec2 = seg_vers_vec((p2, p3))

    vec3 = seg_vers_vec((p1, p4))
    vec4 = seg_vers_vec((p2, p4))

    if determinant(vec1, vec2) * determinant(vec3, vec4) < 0:
        return True

    return False
    
    
seg1 = ((200, 700), (220, 700))
seg2 = ((210, 700), (240, 700))

print(segments_croise(seg1, seg2))
