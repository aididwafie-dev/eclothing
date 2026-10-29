export type DropdownOption = { id: string; value: string };

export type AdminUserFilter =
  | "incomplete_rank"
  | "incomplete_name"
  | "incomplete_unit"
  | "incomplete_personal"
  | "incomplete_uniform"
  | "complete_all";

export type AdminUserRow = {
  id: string;
  sId: string | null;
  hasOrdered: boolean;
  rankName: string | null;
  name: string | null;
  unitName: string | null;
  blocked: boolean;
  activated: boolean;
  createdAt: string | null;
  updatedAt: string | null;
};

export type AdminUsersResponse = {
  users: AdminUserRow[];
  recordsTotal: number;
  recordsFiltered: number;
  page: number;
  perPage: number;
  filter: AdminUserFilter | null;
};

export type AdminUserDetailResponse = {
  user: { id: string; sId: string; email: string };
  personalDetails: {
    sId: string;
    name: string;
    service: string | null;
    ketukanganType: number;
    tred: string | null;
    pangkat: string | null;
    unit: string | null;
    gender: string | null;
    telephoneNumber: string;
    dutyStatus: string | null;
    religion: string | null;
    address: string[];
    namaWaris: string;
    telephoneNumberWaris: string;
    nameTag: string;
    unitLama: string;
    kemLama: string;
    splLama: string;
  } | null;
  position: string;
  dropdowns: {
    services: DropdownOption[];
    tredOfficer: DropdownOption[];
    tredRecruit: DropdownOption[];
    tredBoth: DropdownOption[];
    units: DropdownOption[];
    genders: DropdownOption[];
    dutyStatuses: DropdownOption[];
    states: DropdownOption[];
  };
};

export type SiteSettingsResponse = {
  siteTitle: string;
  logoUrl: string;
};

export type UniformItem = {
  id: string;
  clothesType: string;
  clothesSize: string | null;
  accessory: boolean;
  photoUrl: string | null;
};

export type UniformGroup = {
  uniform: { id: string; uniformType: string; uniformName: string | null; photoUrl: string | null };
  items: UniformItem[];
};

export type UniformsSettingsResponse = {
  groups: UniformGroup[];
};

export type ScaleItem = {
  id: string;
  clothesType: string;
  accessory: boolean;
  maxQuantity: number | null;
};

export type ScaleUniformGroup = {
  uniform: { id: string; label: string };
  hidden: boolean;
  items: ScaleItem[];
};

export type ScaleResponse = {
  ranks: { officer: DropdownOption[]; nonOfficer: DropdownOption[] };
  selectedRankId: string | null;
  uniforms: ScaleUniformGroup[];
  storageReady: boolean;
  hiddenStorageReady: boolean;
};

export type NotificationSettingsResponse = {
  pushEnabled: boolean;
  fcmConfigured: boolean;
  activeUserCount: number;
  deviceCount: number;
};

export type AdminBase = {
  id: string;
  base: string;
  orgName: string | null;
  active: boolean;
  unitCount: number;
};

export type AdminBasesResponse = {
  bases: AdminBase[];
};

export type AdminBaseUnit = {
  id: string;
  value: string;
  baseHq: string | null;
  assignedHere: boolean;
};

export type AdminBaseDetailResponse = {
  base: { id: string; base: string; orgName: string | null; active: boolean };
  units: AdminBaseUnit[];
};

export type AnnouncementAudience = "all" | "unconfirmed" | "confirmed" | "no_orders";

export type AudienceCountsResponse = {
  audienceCounts: Record<AnnouncementAudience, number>;
};

export type PersonalInventoryUniformCount = {
  uniformId: string;
  label: string;
  items: number;
  orders: number;
};

export type PersonalInventoryRow = {
  sId: string | null;
  name: string | null;
  unitName: string | null;
  uniformCounts: PersonalInventoryUniformCount[];
  orderCount: number;
  itemCount: number;
};

export type PersonalInventoryResponse = {
  rows: PersonalInventoryRow[];
  recordsTotal: number;
  recordsFiltered: number;
  page: number;
  perPage: number;
  filters: {
    uniform: string;
    uniformOptions: { id: string; label: string }[];
    month: string;
    monthOptions: { value: string; label: string }[];
    year: string;
    yearOptions: number[];
  };
};

export type PersonalInventoryDetailOrder = {
  id: string;
  status: string;
  statusLabel: string;
  createdAt: string | null;
  collectionDate: string | null;
  remarks: string | null;
  itemCount: number;
  items: { clothes: string; size: string; quantity: number }[];
};

export type PersonalInventoryDetailGroup = {
  uniformId: string;
  label: string;
  orderCount: number;
  itemCount: number;
  orders: PersonalInventoryDetailOrder[];
};

export type PersonalInventoryDetailResponse = {
  member: { sId: string; name: string | null; unitName: string | null; rankName: string | null };
  groups: PersonalInventoryDetailGroup[];
  orderTotal: number;
  itemTotal: number;
  filters: { month: string; year: string; yearOptions: number[] };
};

export type PersonalDetails = {
  s_id: string;
  name: string;
  service: string | null;
  ketukangan_type: number;
  tred: string | null;
  pangkat: string | null;
  unit: string | null;
  gender: string | null;
  telephone_number: string;
  duty_status: string | null;
  religion: string | null;
  address_line1: string;
  address_city: string;
  address_state: string;
  address_postcode: string;
  nama_waris: string;
  telephone_number_waris: string;
  name_tag: string;
  unit_lama: string;
  kem_lama: string;
  spl_lama: string;
};

export type ProfileResponse = {
  personalDetails: PersonalDetails | null;
  position: string;
  dropdowns: {
    services: DropdownOption[];
    tredOfficer: DropdownOption[];
    tredRecruit: DropdownOption[];
    tredBoth: DropdownOption[];
    units: DropdownOption[];
    genders: DropdownOption[];
    dutyStatuses: DropdownOption[];
    states: DropdownOption[];
  };
};

export type ClothingSizeItem = {
  clothesId: string;
  clothesType: string;
  sizeKind: "select" | "text";
  sizeOptions: string[];
  placeholder: string | null;
  savedValue: string;
};

export type ClothingSizeGroup = {
  uniform: { id: string; uniformType: string; uniformName: string | null };
  items: ClothingSizeItem[];
};

export type ClothingSizesResponse = {
  groups: ClothingSizeGroup[];
};

export type AdminOrderListItem = {
  id: string;
  sId: string | null;
  name: string | null;
  unitName: string | null;
  uniformLabel: string;
  itemCount: number;
  status: string;
  statusLabel: string;
  createdAt: string | null;
  updatedAt: string | null;
};

export type AdminOrdersResponse = {
  orders: AdminOrderListItem[];
  recordsFiltered: number;
  page: number;
  perPage: number;
  filters: {
    status: string;
    statusOptions: { key: string; label: string }[];
    unit: string | null;
    unitOptions: { id: string; value: string }[];
  };
};

export type AdminOrderLine = {
  id: string;
  clothes: string;
  size: string;
  quantity: number;
  approvedQuantity: number | null;
  issueVoucher: string | null;
  uniformLabel: string;
};

export type UniformOption = {
  id: string;
  uniformType: string;
  uniformName: string | null;
  active: boolean;
};

export type UniformsResponse = {
  uniforms: UniformOption[];
};

export type ClothesItem = {
  clothesSlug: string;
  clothesType: string;
  clothesSize: string | null;
  accessory: boolean;
  sizeKind: "toggle" | "fixed" | "text" | "select" | "multiselect";
  sizeOptions: string[];
  inCart: boolean;
  cartValue: unknown;
  orderedSize: string | null;
  quantity: number;
  maxQuantity: number | null;
  imageUrl: string | null;
};

export type ClothesResponse = {
  clothes: ClothesItem[];
};

export type CartItem = {
  uniformsId: string;
  uniformName: string;
  clothesSlug: string;
  clothesType: string;
  size: unknown;
  quantity: number;
  imageUrl: string | null;
};

export type CartResponse = {
  items: CartItem[];
  count: number;
};

export type OrderItem = {
  clothes: string;
  size: string;
  uniformsId: string;
};

export type MemberOrder = {
  id: string;
  uniformsId: string;
  uniformType: string;
  uniformName: string | null;
  uniforms: { id: string; type: string; name: string | null }[];
  itemCount: number;
  status: string;
  statusLabel: string;
  editable: boolean;
  uniformPhotoUrl: string | null;
  collectionDate: string | null;
  remarks: string | null;
  createdAt: string | null;
  updatedAt: string | null;
  items: OrderItem[];
};

export type OrdersResponse = {
  orders: MemberOrder[];
};

export type NotificationItem = {
  id: number;
  type: string;
  title: string;
  body: string;
  order_id: number | null;
  read: boolean;
  created_at: string;
};

export type NotificationsResponse = {
  notifications: NotificationItem[];
  unread_count: number;
};

export type AdminOrderDetailResponse = {
  order: {
    id: string;
    sId: string | null;
    email: string | null;
    name: string | null;
    unitName: string | null;
    uniformLabel: string;
    status: string;
    statusLabel: string;
    remarks: string | null;
    collectionDate: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    orderReference: string;
  };
  items: AdminOrderLine[];
  allowedStatuses: string[];
  canApproveQuantities: boolean;
  canRecordIssueVouchers: boolean;
  isOwnOrder: boolean;
};

export type AdminUnit = {
  id: string;
  value: string;
  baseHq: string | null;
  org: string | null;
  baseHqName?: string | null;
};

export type AdminUnitsResponse = { units: AdminUnit[] };

export type AdminUnitDetailResponse = { unit: AdminUnit; bases: DropdownOption[] };

/** ketukangans.officer_recruit: 1 officer, 2 other rank, 3 both. */
export type AdminTred = { id: string; value: string; officerRecruit: number };

export type AdminTredsResponse = { treds: AdminTred[] };

export type AdminTredDetailResponse = { tred: AdminTred };

export type AdminAccountRow = {
  id: string;
  name: string;
  email: string;
  serviceId: string;
  jawatanRank: string;
  role: "superadmin" | "orders";
  active: boolean;
};

export type AdminIdentity = {
  sId: string;
  pangkatId: string;
  jawatan: string;
  role: string;
  baseId: string;
};

export type AdminIdentityOptions = {
  pangkats: DropdownOption[];
  bases: DropdownOption[];
  roles: DropdownOption[];
};

export type AdminAccountDetailResponse = AdminIdentityOptions & {
  admin: AdminIdentity & { id: string; name: string; email: string };
};

export type AdminCandidateResponse = AdminIdentityOptions & {
  candidate: { name: string; email: string; username: string; pangkatId: string };
};
