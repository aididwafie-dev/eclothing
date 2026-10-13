export type DropdownOption = { id: string; value: string };

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
  sizeKind: "toggle" | "fixed" | "text" | "select" | "multiselect";
  sizeOptions: string[];
  inCart: boolean;
  cartValue: unknown;
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
